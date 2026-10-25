<?php
class Delete extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('city', 'company/cities', 'cities');
        $this->langArr = [];
    }
    public function run($city_id)
    {
        $response = $this->destroy($city_id);

        if ($this->hasError($response)){
            $this->jsonError($response, 'Error deleting city.');
        }

        $this->jsonSuccess('City deleted successfully.');
    }

}
