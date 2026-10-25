<!-- Modal para Estados -->
<div class="modal fade" id="modalStates" tabindex="-1" role="dialog" aria-labelledby="modalStatesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStatesLabel">Estados</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="statesSearch">Buscar Estado:</label>
                    <input type="text" class="form-control" id="statesSearch" placeholder="Buscar por nombre...">
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover">
                        <thead class="thead-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                            </tr>
                        </thead>
                        <tbody id="statesTableBody">
                            <tr>
                                <td colspan="2" class="text-center">
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
        var allStates = [];
        
        // Cuando se abre el modal, cargar estados
        $('#modalStates').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            currentCountryId = button.data('country-id');
            var countryName = button.data('country-name') || 'País';
            
            $('#modalStatesLabel').text('Estados - ' + countryName);
            loadStates(currentCountryId);
        });
        
        // Función para cargar estados
        function loadStates(countryId) {
            var $tbody = $('#statesTableBody');
            $tbody.html('<tr><td colspan="2" class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Cargando...</span></div></td></tr>');
            
            var apiToken = '<?= isset($apiToken) ? addslashes($apiToken) : "" ?>';
            var apiBaseUrl = '<?= isset($apiBaseUrl) ? addslashes($apiBaseUrl) : "/api" ?>';
            
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
                success: function(response) {
                    allStates = Array.isArray(response) ? response : (response.data || []);
                    renderStates(allStates);
                },
                error: function(xhr) {
                    $tbody.html('<tr><td colspan="2" class="text-center text-danger">Error al cargar los estados</td></tr>');
                }
            });
        }
        
        // Función para renderizar estados
        function renderStates(states) {
            var $tbody = $('#statesTableBody');
            
            if (states.length === 0) {
                $tbody.html('<tr><td colspan="2" class="text-center text-muted">No hay estados disponibles</td></tr>');
                return;
            }
            
            var html = '';
            states.forEach(function(state) {
                html += '<tr>';
                html += '<td>' + (state.id || 'N/A') + '</td>';
                html += '<td>' + (state.name || 'N/A') + '</td>';
                html += '</tr>';
            });
            
            $tbody.html(html);
        }
        
        // Búsqueda en tiempo real
        $('#statesSearch').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            var filteredStates = allStates.filter(function(state) {
                return !searchTerm || (state.name && state.name.toLowerCase().includes(searchTerm));
            });
            renderStates(filteredStates);
        });
    }
})();
</script>

