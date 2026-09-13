<aside class="left-sidebar" data-sidebarbg="skin5">

        <!-- Sidebar scroll-->

        <div class="scroll-sidebar">

          <!-- Sidebar navigation-->

          <nav class="sidebar-nav">

            <ul id="sidebarnav" class="pt-4">

            <?php if ($_SESSION['usuario_rol'] != 'admin') { ?>

              <li class="sidebar-item">

                <a

                  class="sidebar-link waves-effect waves-dark sidebar-link"

                  href="index.html"

                  aria-expanded="false"

                  ><i class="mdi mdi-view-dashboard"></i

                  ><span class="hide-menu">Dashboard</span></a

                >

              </li>

              <?php } ?>
              <li class="sidebar-item">

<a

  class="sidebar-link waves-effect waves-dark sidebar-link"

  href="index.php"

  aria-expanded="false"

  ><i class="mdi mdi-view-dashboard"></i

  ><span class="hide-menu">Asistencia</span></a

>

</li>
              <li class="sidebar-item">

                <a

                  class="sidebar-link waves-effect waves-dark sidebar-link"

                  href="charts.php"

                  aria-expanded="false"

                  ><i class="mdi mdi-chart-bar"></i

                  ><span class="hide-menu">Charts</span></a

                >

              </li>
              <li class="sidebar-item">

                <a

                  class="sidebar-link waves-effect waves-dark sidebar-link"

                  href="listaninos.php"

                  aria-expanded="false"

                  ><i class="mdi mdi-view-dashboard"></i

                  ><span class="hide-menu">Lista de niños</span></a

                >

              </li>

              <li class="sidebar-item">

                <a

                  class="sidebar-link waves-effect waves-dark sidebar-link"

                  href="reportes.php"

                  aria-expanded="false"

                  ><i class="mdi mdi-view-dashboard"></i

                  ><span class="hide-menu">Reporte</span></a

                >

              </li>

              <li class="sidebar-item">

                <a

                  class="sidebar-link waves-effect waves-dark sidebar-link"

                  href="cumpleaños.php"

                  aria-expanded="false"

                  ><i class="mdi mdi-cake-variant"></i

                  ><span class="hide-menu">Cumpleaños</span></a

                >

              </li>

              

            </ul>

          </nav>

          <!-- End Sidebar navigation -->

        </div>

        <!-- End Sidebar scroll-->

      </aside>