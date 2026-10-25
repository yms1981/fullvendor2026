<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('state', 'company/states', 'states');
        $this->langArr = [];
    }
    public function run($state_id)
    {
        $response = $this->destroy($state_id);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error deleting state.');
        }

        $this->jsonSuccess('State deleted successfully.');
    }

}
