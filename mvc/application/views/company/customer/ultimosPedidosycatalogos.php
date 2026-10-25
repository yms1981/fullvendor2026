   <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="col-6">
              <h4 class="text-uppercase text-left text-success mb-4"><?= strtoupper($langcliente['ultimos_catalogos_enviados']) ?></h4>
              <?php $this->load->view('company/customer/CatalogosEnviadosClientes', $data); ?>
          </div>

        </div>
        <div class="col-6">
            <h4 class="text-uppercase text-left text-success mb-4"><?= strtoupper($langcliente['ultimas_ordenes']) ?></h4>
            <?php $this->load->view('company/customer/OrdenesClientes', $data); ?>

        </div>
      </div>
    </div>
  </div>
