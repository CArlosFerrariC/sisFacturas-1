<li class="nav-item">
    <a class="nav-link" data-widget="pushmenu" href="#"
       @if(config('adminlte.sidebar_collapse_remember'))
           data-enable-remember="true"
       @endif
       @if(!config('adminlte.sidebar_collapse_remember_no_transition'))
           data-no-transition-after-reload="false"
       @endif
       @if(config('adminlte.sidebar_collapse_auto_size'))
           data-auto-collapse-size="{{ config('adminlte.sidebar_collapse_auto_size') }}"
        @endif>
        <i class="fas fa-bars"></i>
        <span class="sr-only">{{ __('adminlte::adminlte.toggle_navigation') }}</span>
    </a>
</li>
@if(user_rol(\App\Models\User::ROL_ASESOR)||user_rol(\App\Models\User::ROL_ADMIN))
    <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="addalert">
            <i class="fas fa-bell"></i>
            Agregar Alerta
        </a>
    </li>
@endif
