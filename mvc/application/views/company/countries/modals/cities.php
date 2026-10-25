<!-- Modal para Ciudades -->
<div class="modal fade" id="modalCities" tabindex="-1" role="dialog" aria-labelledby="modalCitiesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCitiesLabel">Ciudades</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="citiesSearch">Buscar Ciudad:</label>
                    <input type="text" class="form-control" id="citiesSearch" placeholder="Buscar por nombre...">
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover">
                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody id="citiesTableBody">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Cargando...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (typeof jQuery !== 'undefined') {
        var currentCountryId = null;
        var allCities = [];
        
        // Cuando se abre el modal, cargar ciudades
        $('#modalCities').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            currentCountryId = button.data('country-id');
            var countryName = button.data('country-name') || 'País';
            
            $('#modalCitiesLabel').text('Ciudades - ' + countryName);
            loadCities(currentCountryId);
        });
        
        // Función para cargar ciudades
        function loadCities(countryId) {
            var $tbody = $('#citiesTableBody');
            $tbody.html('<tr><td colspan="3" class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></td></tr>');
            
            var apiToken = '<?= isset($apiToken) ? addslashes($apiToken) : "" ?>';
            var apiBaseUrl = '<?= isset($apiBaseUrl) ? addslashes($apiBaseUrl) : "/api" ?>';
            
            // Primero obtener los estados del país, luego las ciudades de esos estados
            $.ajax({
                url: apiBaseUrl + '/v2/state',
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Authorization': 'Bearer ' + apiToken
                },
                data: {
                    'all': true,
                    'props': ['id', 'name', 'country_id'],
                    'country_id': countryId
                },
                success: function(statesResponse) {
                    var states = Array.isArray(statesResponse) ? statesResponse : (statesResponse.data || []);
                    var stateIds = states.map(function(s) { return s.id; });
                    
                    if (stateIds.length === 0) {
                        $tbody.html('<tr><td colspan="3" class="text-center text-muted">No hay ciudades disponibles (no hay estados)</td></tr>');
                        return;
                    }
                    
                    // Cargar ciudades de forma optimizada: procesar en lotes para evitar sobrecarga
                    var BATCH_SIZE = 10; // Procesar 10 estados a la vez
                    var allCitiesData = [];
                    var processedBatches = 0;
                    var totalBatches = Math.ceil(stateIds.length / BATCH_SIZE);
                    
                    function processBatch(batchIndex) {
                        var batchStart = batchIndex * BATCH_SIZE;
                        var batchEnd = Math.min(batchStart + BATCH_SIZE, stateIds.length);
                        var batchStateIds = stateIds.slice(batchStart, batchEnd);
                        
                        if (batchStateIds.length === 0) {
                            // Todos los lotes procesados
                            if (processedBatches === totalBatches) {
                                // Enriquecer con información del estado
                                allCitiesData = allCitiesData.map(function(city) {
                                    var state = states.find(function(s) { return s.id == city.state_id; });
                                    city.state_name = state ? state.name : 'N/A';
                                    return city;
                                });
                                
                                allCities = allCitiesData;
                                renderCities(allCities);
                            }
                            return;
                        }
                        
                        // Cargar ciudades de este lote
                        var batchPromises = batchStateIds.map(function(stateId) {
                            return $.ajax({
                                url: apiBaseUrl + '/v2/city',
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'Authorization': 'Bearer ' + apiToken
                                },
                                data: {
                                    'all': true,
                                    'props': ['id', 'name', 'state_id'],
                                    'state_id': stateId
                                }
                            });
                        });
                        
                        $.when.apply($, batchPromises).then(function() {
                            for (var i = 0; i < arguments.length; i++) {
                                var response = arguments[i][0];
                                var cities = Array.isArray(response) ? response : (response.data || []);
                                allCitiesData = allCitiesData.concat(cities);
                            }
                            
                            processedBatches++;
                            
                            // Procesar siguiente lote
                            if (batchIndex + 1 < totalBatches) {
                                processBatch(batchIndex + 1);
                            } else {
                                // Todos los lotes procesados
                                // Enriquecer con información del estado
                                allCitiesData = allCitiesData.map(function(city) {
                                    var state = states.find(function(s) { return s.id == city.state_id; });
                                    city.state_name = state ? state.name : 'N/A';
                                    return city;
                                });
                                
                                allCities = allCitiesData;
                                renderCities(allCities);
                            }
                        }).fail(function() {
                            $tbody.html('<tr><td colspan="3" class="text-center text-danger">Error al cargar las ciudades</td></tr>');
                        });
                    }
                    
                    // Iniciar procesamiento del primer lote
                    processBatch(0);
                },
                error: function(xhr) {
                    $tbody.html('<tr><td colspan="3" class="text-center text-danger">Error al cargar los estados</td></tr>');
                }
            });
        }
        
        // Función para renderizar ciudades
        function renderCities(cities) {
            var $tbody = $('#citiesTableBody');
            
            if (cities.length === 0) {
                $tbody.html('<tr><td colspan="3" class="text-center text-muted">No hay ciudades disponibles</td></tr>');
                return;
            }
            
            var html = '';
            cities.forEach(function(city) {
                html += '<tr>';
                html += '<td>' + (city.id || 'N/A') + '</td>';
                html += '<td>' + (city.name || 'N/A') + '</td>';
                html += '<td>' + (city.state_name || 'N/A') + '</td>';
                html += '</tr>';
            });
            
            $tbody.html(html);
        }
        
        // Búsqueda en tiempo real
        $('#citiesSearch').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            var filteredCities = allCities.filter(function(city) {
                return !searchTerm || 
                       (city.name && city.name.toLowerCase().includes(searchTerm)) ||
                       (city.state_name && city.state_name.toLowerCase().includes(searchTerm));
            });
            renderCities(filteredCities);
        });
    }
})();
</script>
