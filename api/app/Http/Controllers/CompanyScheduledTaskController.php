<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyScheduledTask;
use App\Models\CompanyScheduledTaskLog;
use App\Services\Erp\ErpProductImageQueueService;
use App\Services\Erp\ErpScheduledTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(name: 'CompanyScheduledTasks', description: 'ERP scheduled pull tasks')]
class CompanyScheduledTaskController extends Controller
{
    public function __construct(private ErpScheduledTaskService $service) {}

    #[OA\Get(
        path: 'api/v2/company/{company}/scheduled-tasks',
        operationId: 'companyScheduledTasksIndex',
        summary: 'List ERP scheduled tasks for a company',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Task list'),
            new OA\Response(response: 404, description: 'Company not found'),
        ]
    )]
    public function listTasks(Company $company): JsonResponse
    {
        $this->service->ensureTasksForCompany($company);

        $tasks = CompanyScheduledTask::query()
            ->where('company_id', $company->company_id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $tasks,
            'erp_enabled' => (bool) ($company->erp_enabled ?? false),
            'erp_url' => $company->erp_url,
            'erp_has_token' => $company->erp_has_token,
            'task_types' => CompanyScheduledTask::taskTypes(),
            'frequencies' => CompanyScheduledTask::frequencies(),
        ]);
    }

    #[OA\Put(
        path: 'api/v2/company/{company}/scheduled-tasks/{taskType}',
        operationId: 'companyScheduledTasksUpdate',
        summary: 'Update enable/frequency for a scheduled task',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskType', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateTask(Request $request, Company $company, string $taskType): JsonResponse
    {
        if (! in_array($taskType, CompanyScheduledTask::taskTypes(), true)) {
            return response()->json(['message' => 'Invalid task type'], 422);
        }

        $data = $request->validate([
            'enabled' => ['sometimes', 'boolean'],
            'frequency' => ['sometimes', 'string', 'in:'.implode(',', CompanyScheduledTask::frequencies())],
        ]);

        $this->service->ensureTasksForCompany($company);

        $task = CompanyScheduledTask::query()
            ->where('company_id', $company->company_id)
            ->where('task_type', $taskType)
            ->firstOrFail();

        if (array_key_exists('enabled', $data)) {
            $task->enabled = (bool) $data['enabled'];
        }
        if (array_key_exists('frequency', $data)) {
            $task->frequency = (string) $data['frequency'];
        }

        if ($task->enabled && $task->frequency !== CompanyScheduledTask::FREQUENCY_MANUAL) {
            $task->next_run_at = $task->next_run_at ?? $task->computeNextRunAt(now());
            if ($task->next_run_at === null) {
                $task->next_run_at = $task->computeNextRunAt(now());
            }
        } else {
            $task->next_run_at = null;
        }

        $task->save();

        return response()->json(['data' => $task->fresh()]);
    }

    #[OA\Post(
        path: 'api/v2/company/{company}/scheduled-tasks/{taskType}/run',
        operationId: 'companyScheduledTasksRun',
        summary: 'Run an ERP scheduled task now',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'taskType', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Run result'),
            new OA\Response(response: 422, description: 'ERP disabled or invalid'),
        ]
    )]
    public function runTask(Company $company, string $taskType): JsonResponse
    {
        if (! in_array($taskType, CompanyScheduledTask::taskTypes(), true)) {
            return response()->json(['message' => 'Invalid task type'], 422);
        }

        if (! (bool) ($company->erp_enabled ?? false)) {
            return response()->json(['message' => 'ERP connection is disabled'], 422);
        }

        $this->service->ensureTasksForCompany($company);

        $task = CompanyScheduledTask::query()
            ->where('company_id', $company->company_id)
            ->where('task_type', $taskType)
            ->firstOrFail();

        $result = $this->service->runTask($task, CompanyScheduledTaskLog::TRIGGER_MANUAL);

        return response()->json([
            'data' => $task->fresh(),
            'result' => $result,
        ], $result['status'] === 'success' ? Response::HTTP_OK : 422);
    }

    #[OA\Get(
        path: 'api/v2/company/{company}/scheduled-tasks/logs',
        operationId: 'companyScheduledTasksLogs',
        summary: 'List ERP scheduled task execution logs',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'task_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Log list'),
        ]
    )]
    public function listLogs(Request $request, Company $company): JsonResponse
    {
        $query = CompanyScheduledTaskLog::query()
            ->where('company_id', $company->company_id)
            ->orderByDesc('id');

        $taskType = trim((string) $request->query('task_type', ''));
        if ($taskType !== '' && in_array($taskType, CompanyScheduledTask::taskTypes(), true)) {
            $query->where('task_type', $taskType);
        }

        $status = trim((string) $request->query('status', ''));
        if (in_array($status, ['success', 'error'], true)) {
            $query->where('status', $status);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 25)));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    #[OA\Get(
        path: 'api/v2/company/{company}/scheduled-tasks/image-queue',
        operationId: 'companyScheduledTasksImageQueue',
        summary: 'ERP product image download queue progress',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'recent', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Queue progress'),
        ]
    )]
    public function imageQueueProgress(Request $request, Company $company, ErpProductImageQueueService $queue): JsonResponse
    {
        $recent = max(1, min(100, (int) $request->query('recent', 40)));
        $companyId = (int) $company->company_id;

        // Si hay cola pendiente, arrancarla sola (poll UI / refresh).
        $auto = filter_var($request->query('auto_drain', true), FILTER_VALIDATE_BOOLEAN);
        if ($auto) {
            $queue->autoDrainPending($companyId, 8);
        }

        return response()->json([
            'data' => $queue->progressForCompany($companyId, $recent),
        ]);
    }

    #[OA\Post(
        path: 'api/v2/company/{company}/scheduled-tasks/image-queue/process',
        operationId: 'companyScheduledTasksImageQueueProcess',
        summary: 'Process a batch of pending ERP product image downloads',
        security: [['bearerAuth' => []]],
        tags: ['CompanyScheduledTasks'],
        parameters: [
            new OA\Parameter(name: 'company', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Batch processed'),
        ]
    )]
    public function processImageQueue(Request $request, Company $company, ErpProductImageQueueService $queue): JsonResponse
    {
        @set_time_limit(120);
        $companyId = (int) $company->company_id;
        $limit = max(1, min(20, (int) $request->input('limit', $request->query('limit', 12))));
        $queue->pruneIrrelevantPending($companyId);
        $stats = $queue->processPending($limit, $companyId);

        // Seguir en segundo plano si aún hay pendientes.
        if ((int) ($stats['remaining_pending'] ?? 0) > 0) {
            $queue->continueDrainAfterResponse($companyId);
        }

        return response()->json([
            'message' => 'Image queue batch processed',
            'result' => $stats,
            'data' => $queue->progressForCompany($companyId, 40),
        ]);
    }
}
