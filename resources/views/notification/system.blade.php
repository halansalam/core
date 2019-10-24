    <?php
        $notification = \App\AdminNotification::where('id',(request('notification_id')))->first();
        if(!$notification){
            header("Location: /", true);
            exit();
        }
        switch ($notification->type){
            case "cert_request":
                list($hostname, $port, $server_id) = explode(":",$notification->message);
                $url = route('certificate_add_page') . "?notification_id=$notification->id&hostname=$hostname&port=$port&server_id=$server_id";
                header("Location: $url", true);
                exit();
                break;
            case "liman_update":
                $url = route('settings') . "#update";
                $notification->update([
                    "read" => "true"
                ]);
                header("Location: $url", true);
                exit();
                break;
            case "health_problem":
                $url = route('settings') . "#health";
                $notification->update([
                    "read" => "true"
                ]);
                header("Location: $url", true);
                exit();
                break;
            default:
                break;
        }

    ?>
@extends('layouts.app')

@section('content')
    @include('l.errors')
    <ul class="timeline">
        <li class="time-label">
        <span class="bg-green">
            {{\Carbon\Carbon::parse($notification->created_at)->format("d.m.Y")}}
        </span>
        </li>
        <li>
            <div class="timeline-item">
                <span class="time"><i class="fa fa-clock-o"></i> {{\Carbon\Carbon::parse($notification->created_at)->format("h:i:s")}}</span>

                <h3 class="timeline-header">
                    @if(!$notification->read)<a href="javascript:void(0)">@endif
                        {{$notification->title}}
                        @if(!$notification->read)</a>@endif
                </h3>

                <div class="timeline-body">
                    {!!$notification->message!!}
                </div>
                <div class="timeline-footer">
                    @if(!$notification->read)
                        <a class="btn btn-primary btn-xs mark_read"
                           notification-id="{{$notification->id}}">{{__('Okundu Olarak İşaretle')}}</a>
                    @endif
                </div>
            </div>
        </li>
    </ul>
    <script>
        $('.mark_read').click(function () {
            let data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_read')}}', data, function (response) {
                location.reload();
            });
        });
        $('.delete_not').click(function () {
            let data = new FormData();
            data.append('notification_id', $(this).attr('notification-id'));
            request('{{route('notification_delete')}}', data, function (response) {
                location.href = "{{route('all_user_notifications')}}";
            });
        });
    </script>
@endsection