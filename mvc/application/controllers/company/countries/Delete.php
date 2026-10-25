<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/countries', 'countries');
        $this->langArr = [];
    }
    public function run($country_id)
    {
        $response = $this->destroy($country_id);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error deleting country.');
        }

        $this->jsonSuccess('Country deleted successfully.');
    }

}
