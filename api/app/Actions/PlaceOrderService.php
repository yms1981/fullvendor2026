<?php

namespace App\Actions;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\PlaceOrderMailResource;
use App\Mail\PlaceOrderMail;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Services\CompanyPlanLimitService;
use App\Services\CompanySendTemplateService;
use App\Services\OpenCartOrderSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlaceOrderService
{
    public function __construct(private CompanyPlanLimitService $planLimits)
    {
    }

    public function execute(PlaceOrderRequest $request, Order $order): \Illuminate\Http\JsonResponse
    {
        try {
            if ($order->status_id != StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING)) {
                return $this->errorResponse('Estado inválido', 'La orden no se encuentra en estado de compra', Response::HTTP_BAD_REQUEST);
            }

            $companyId = (int) ($order->company_id ?? 0);
            if ($companyId > 0) {
                $denied = $this->planLimits->assertCanCreateOrder($companyId);
                if ($denied !== null) {
                    $payload = json_decode($denied->getContent(), true);
                    return $this->errorResponse(
                        'Límite de plan alcanzado',
                        (string) ($payload['message'] ?? 'Limit reached'),
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }

            $this->processOrder($order);

            $validatedRequest = $request->validated();
            $new_address = $validatedRequest['new_address'] ?? null;
            $notes = $validatedRequest['notes'] ?? null;
            $mainData = Arr::except($validatedRequest, ['new_address', 'notes']);

            if ($new_address){
                $address = $this->createAddress($new_address);
            }

            if ($notes){
                if (!empty($notes['shipping_notes']))
                    $order->notes()->create([
                        'user_id' => $mainData['user_placed_order'],
                        'type' => OrderNoteTypes::Shipping,
                        'note' => $notes['shipping_notes']
                    ]);

                if (!empty($notes['comment_order']))
                    $order->notes()->create([
                        'user_id' => $mainData['user_placed_order'],
                        'type' => OrderNoteTypes::Order,
                        'note' => $notes['comment_order']
                    ]);
            }

            $order->update(array_merge(
                $request->validated(),
                [
                    'status_id' => StatusOrder::getIdByCode(OrderStatusCodes::PLACED),
                    'address_id' => $address?->id ?? null,
                ]
            ));

            $this->sendMailIfRequired($order,$request['user_type']);

            $token = trim((string) ($order->token_pass ?? ''));
            if ($token !== '' && (int) ($order->company_id ?? 0) > 0) {
                try {
                    app(OpenCartOrderSyncService::class)->closeCompleted(
                        (int) $order->company_id,
                        null,
                        $token,
                        (int) $order->order_id
                    );
                } catch (Throwable $e) {
                    report($e);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $order->fresh('order_details.product') //needed?
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse('Error de validación', $e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Throwable $e) {
            return $this->errorResponse('Error del servidor', $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function errorResponse(string $error, string $message, int $statusCode): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
            'message' => $message
        ], $statusCode);
    }

    /**
     * @throws Throwable
     */
    private function processOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $this->verifyOrderDetails($order);

            if ($order->order_details->isEmpty()) {
                throw ValidationException::withMessages([
                    'error' => 'La orden no tiene productos asociados'
                ]);
            }

            foreach ($order->order_details as $detail) {
                $product = $detail->product;
                $qty = $detail->qty;

                if ($order->company->sell_with_inventory) {
                    $updated = Product::where('product_id', $product->id)
                        ->where('stock', '>=', $qty)
                        ->decrement('stock', $qty);

                    if (!$updated) {
                        throw ValidationException::withMessages([
                            'quantity' => "Stock insuficiente para el producto {$product->name} al momento de confirmar."
                        ]);
                    }
                }
            }
        });
    }

    private function verifyOrderDetails(Order $order): void
    {
        $order->setRelation(
            'order_details',
            $order->order_details->filter(function ($detail) {
                if ($detail->qty <= 0 || $detail->qty === null) {
                    $detail->delete();
                    return false;
                }
                return true;
            })
        );

        foreach ($order->order_details as $detail) {
            $product = $detail->product;
            $qty = $detail->qty;

            if (!$product->validMinimumQuantity($qty)) {
                throw ValidationException::withMessages([
                    'quantity' => "La cantidad mínima de compra para el producto {$product->name} es {$product->minimum_stock}"
                ]);
            }

            if ($order->company->sell_with_inventory && !$product->hasStock($qty)) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente para el producto {$product->name}"
                ]);
            }
        }
    }

    private function sendMailIfRequired(Order $order, string $userType): void
    {
        $company = $order->company;

        if ($userType === 'seller') {
            if ($company->email_on_salesman_order) {
                $this->sendOrderMail(optional($order->seller?->user)->email, $order);
            }

            if ($company->email_client_on_salesman_order) {
                $this->sendOrderMail(optional($order->customer?->user)->email, $order);
            }
        }

        if ($userType === 'customer') {
            $this->sendOrderMail(optional($order->customer?->user)->email, $order);
        }
    }

    private function sendOrderMail(?string $email, Order $order): void
    {
        if (! $email) {
            return;
        }

        $order->loadMissing(['company', 'customer', 'statusOrder']);
        /** @var CompanySendTemplateService $templates */
        $templates = app(CompanySendTemplateService::class);
        $rendered = $templates->renderForOrder($order, 'email');
        if ($rendered !== null && trim($rendered['body']) !== '') {
            $subject = trim($rendered['subject']) !== ''
                ? $rendered['subject']
                : trim((string) ($order->company?->name ?? '').' - '.(string) ($order->order_number ?? ''));
            Mail::html($rendered['body'], function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            return;
        }

        Mail::to($email)->send(new PlaceOrderMail((new PlaceOrderMailResource($order))->toArray(new Request())));
    }

    private function createAddress(array $data)
    {
        return Address::create([
            'street'        => $data['delivery_address'],
            'zipcode'       => $data['zip_code'],
            'zone'          => $data['zone'],
            'country_id'    => $data['country'],
            'state_id'      => $data['state'],
            'city_id'       => $data['city'],
        ]);
    }
}
