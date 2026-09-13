<!-- Modal -->

<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h2 id="modalTitle">Registro de Niño</h2>
        <form class="form-horizontal" id="registroForm">
            
            <div class="card-body">
                <h4 class="card-title">Información</h4>
                <!-- ID oculto para edición -->
                <input type="hidden" id="idNino" name="idNino" />
                <div class="form-group row">
                    <label for="fname" class="col-sm-3 text-end control-label col-form-label">Nombre</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="fname" name="fname" placeholder="Nombre" />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="lname" class="col-sm-3 text-end control-label col-form-label">Apellidos</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="lname" name="lname" placeholder="Apellidos" />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="fechaNacimiento" class="col-sm-3 text-end control-label col-form-label">Fecha de Nacimiento</label>
                    <div class="col-sm-9">
                        <input type="date" class="form-control" id="fechaNacimiento" name="fechaNacimiento" />
                    </div>
                </div>
                
                <div class="form-group row">
                    <label for="padrename" class="col-sm-3 text-end control-label col-form-label">Nombre del padre</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="padrename" name="padrename" placeholder="Nombre del padre" />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="padrephone" class="col-sm-3 text-end control-label col-form-label">Telefono del padre</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="padrephone" name="padrephone" placeholder="Telefono del Padre" />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="diagnostico" class="col-sm-3 text-end control-label col-form-label">
                        Diagnóstico
                    </label>
                    <div class="col-sm-9 d-flex align-items-center">
                        <div class="form-check form-switch">
                             <input class="form-check-input" type="checkbox" id="diagnostico" name="diagnostico" value="1">
                            <label class="form-check-label" for="diagnostico">Tiene diagnóstico</label>
                        </div>
                    </div>
                </div>
                <div class="form-group row" id="diagnosticosBox" style="display:none;">
                    <label class="col-sm-3 text-end control-label col-form-label">
                        Conductas
                    </label>
                    <div class="col-sm-9">
                        <div id="listaDiagnosticos">
                            <!-- Se llena por AJAX -->
                        </div>
                    </div>
                </div>
                <div class="border-top">
                    <div class="card-body">
                        <button type="submit" id="modalButton" class="btn btn-primary">Registrar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>



    <!-- Modal de Asistencia -->

<div id="asistenciaModal" class="modal">

    <div class="modal-content">

    <span class="closeasis">&times;</span>

        <h2>Registrar Asistencia</h2>

        <form id="asistenciaForm">

            <input type="hidden" id="id_nino" name="id_nino" value="">

            <!-- Contenido del formulario de Asistencia -->

            <div class="form-group">

                <label for="id_clase">Área</label>

                <select class="form-control" id="clase" name="clase" required>

                    <option value="">Seleccione un área</option>

                </select>

            </div>

            <div class="form-group">

                <label for="fecha_asistencia">Fecha</label>

                <input type="date" class="form-control" id="fecha_asistencia" name="fecha_asistencia" required >

            </div>

            <div class="form-group">

                <label for="hora_entrada">Hora de entrada</label>

                <input type="time" class="form-control" id="hora_entrada" name="hora_entrada" required >

            </div>

            <button type="submit" class="btn btn-primary">Registrar Asistencia</button>

        </form>

    </div>

</div>

