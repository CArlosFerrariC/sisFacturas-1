<?php

namespace App\Http\Controllers;

use App\Models\Devolucion;
use Illuminate\Http\Request;
use App\Notifications\InvoicePaid;
use Illuminate\Support\Facades\Notification;
use App\Http\Controllers\Controller;
use App\Message;
use App\User;
use App\Notifications\NewMessage;

class NotificationsController extends Controller
{
    /**
     * Get the new notification data for the navbar notification.
     *
     * @param Request $request
     * @return Array
     */
    public function getNotificationsData(Request $request)
    {
        // For the sake of simplicity, assume we have a variable called
        // $notifications with the unread notifications. Each notification
        // have the next properties:
        // icon: An icon for the notification.
        // text: A text for the notification.
        // time: The time since notification was created on the server.
        // At next, we define a hardcoded variable with the explained format,
        // but you can assume this data comes from a database query.

        $notifications = [
            [
                'icon' => 'fas fa-fw fa-envelope',//'fas fa-bell',
                'text' => count(auth()->user()->unreadNotifications) . ' nuevas notificaciones',
                'time' => rand(0, 10) . ' minutes',
            ],
        ];

        // Now, we create the notification dropdown main content.

        $dropdownHtml = '';
        $devoluciones = [];
        if (\Auth::check()) {
            if (\Auth::user()->rol == \App\Models\User::ROL_ADMIN) {
                $devoluciones = Devolucion::query()->with(['cliente', 'pago', 'asesor'])->noAtendidos()->get();
                foreach ($devoluciones as $key => $devolucion) {
                    $icon = "<i class='mr-2 fas fa-fw fa-envelope'></i>";

                    $time = "<span class='float-right text-muted text-sm'>
                       {$devolucion->created_at->diffForHumans()}
                     </span>";

                    $dropdownHtml .= "<a href='" . route('pagos.devolucion', $devolucion) . "' class='dropdown-item'>
                             {$icon}
                             <span class='text-wrap'>
                              Pago por devolver a <b>{$devolucion->cliente->nombre}</b> un valor de <b>{$devolucion->amount_format}</b>
</span>
                             {$time}
                              </a>";

                    if ($key < count($devoluciones) - 1) {
                        $dropdownHtml .= "<div class='dropdown-divider'></div>";
                    }
                }
            }
        }

        /* foreach ($notifications as $key => $not) {
            $icon = "<i class='mr-2 {$not['icon']}'></i>";

            $time = "<span class='float-right text-muted text-sm'>
                       {$not['time']}
                     </span>";

            $dropdownHtml .= "<a href='#' class='dropdown-item'>
                                {$icon}{$not['text']}{$time}
                              </a>";

            if ($key < count($notifications) - 1) {
                $dropdownHtml .= "<div class='dropdown-divider'></div>";
            }
        } */

        foreach (auth()->user()->unreadNotifications as $key => $not) {
            $icon = "<i class='mr-2 fas fa-fw fa-envelope'></i>";

            $time = "<span class='float-right text-muted text-sm'>
                       {$not['created_at']->diffForHumans()}
                     </span>";

            $dropdownHtml .= "<a href='/notifications' class='dropdown-item'>
                                {$icon}{$not['data']['asunto']}{$time}
                              </a>";

            if ($key < count($notifications) - 1) {
                $dropdownHtml .= "<div class='dropdown-divider'></div>";
            }
        }

        // Return the new notification data.

        return [
            'icon' => 'fas fa-envelope',
            'label' => count(auth()->user()->unreadNotifications) + count($devoluciones),
            'label_color' => 'danger',
            'icon_color' => 'white',
            'dropdown' => $dropdownHtml,
        ];
    }

    public function index()
    {
        $postNotifications = auth()->user()->unreadNotifications;
        $devoluciones=[];
        if (\Auth::check()) {
            if (\Auth::user()->rol == \App\Models\User::ROL_ADMIN) {
                $devoluciones = Devolucion::query()->with(['cliente', 'pago', 'asesor'])->noAtendidos()->get();
            }
        }
        //return $devoluciones;
        return view('notifications.index', compact('postNotifications', 'devoluciones'));
    }

    public function markNotification(Request $request)
    {
        auth()->user()->unreadNotifications
            ->when($request->input('id'), function ($query) use ($request) {
                return $query->where('id', $request->input('id'));
            })->markAsRead();
        return response()->noContent();
    }
}
