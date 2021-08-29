const mix = require('laravel-mix');
mix.styles([
    'resources/assets/css/font-awesome.css',
    'resources/assets/css/flag-icon.css',
    'resources/assets/css/font.css',
    'resources/assets/css/bootstrap-tempus.css',
    'resources/assets/css/dataTables.bootstrap4.css',
    'resources/assets/css/select.dataTables.css',
    'resources/assets/css/sweetalert2.min.css',
    'resources/assets/css/buttons.dataTables.min.css',
    'resources/assets/css/adminlte.css',
    'resources/assets/css/dark-mode.css',
    'resources/assets/css/jquery.contextMenu.css',
    'resources/assets/css/jquery-ui.css',
    'resources/assets/css/jstree.css',
    'resources/assets/css/bootstrap-datepicker.css',
    'resources/assets/css/bootstrap-timepicker.css',
    'resources/assets/css/select2.css',
    'resources/assets/css/select2-bootstrap4.css',
    'resources/assets/css/toastr.min.css',
    'resources/assets/css/OverlayScrollbars.css',
    'resources/assets/css/liman.css',
    'resources/assets/css/liman-newlook.css',
], 'public/css/liman.css').version();
mix.combine([
    'resources/assets/js/jquery.js',
    'resources/assets/js/moment.js',
    'resources/assets/js/jquery-ui.js',
    'resources/assets/js/split.min.js',
    'resources/assets/js/bootstrap.bundle.min.js',
    'resources/assets/js/bs-custom-file-input.js',
    'resources/assets/js/tus.lib.js',
    'resources/assets/js/jquery.contextMenu.js',
    'resources/assets/js/bootstrap-tempus.js',
    'resources/assets/js/bootstrap-datepicker.js',
    'resources/assets/js/bootstrap-timepicker.js',
    'resources/assets/js/datatables.js',
    'resources/assets/js/dataTables.bootstrap4.js',
    'resources/assets/js/adminlte.js',
    'resources/assets/js/select2.full.js',
    'resources/assets/js/sweetalert2.min.js',
    'resources/assets/js/Chart.js',
    'resources/assets/js/jstree.js',
    'resources/assets/js/buttons.html5.min.js',
    'resources/assets/js/dataTables.buttons.min.js',
    'resources/assets/js/jquery.inputmask.min.js',
    'resources/assets/js/toastr.min.js',
    'resources/assets/js/echo.common.js',
    'resources/assets/js/pusher.min.js',
    'resources/assets/js/jquery.overlayScrollbars.js',
    'resources/assets/js/liman.js',
    'resources/assets/js/tus.js',
], 'public/js/liman.js').version();
