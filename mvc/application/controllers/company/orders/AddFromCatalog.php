<?php
class AddFromCatalog extends Company_Controller
{
    use IsAction;

    public function run()
    {
        $request = $this->input->get();

        $customer_id = $request['customer'] ?? null;

        if (!$customer_id) {
            show_404();
        }

        $this->setCustomerId($customer_id);

        $this->setApplication('marketplace');
        redirect('/marketplace/catalog');
    }
}
