@extends('layouts.app')

<style>
    .select2-container {
        width: 100% !important;
    }
    .message_left{
        float:left
    }
    .message_right{
        float:right
    }
    .message{
       
       margin-bottom:10px;
    }
    .message div{
        display: inline;
        border:1px solid gray;
        border-radius:10px;
        padding:5px 10px;
    }
    #chatBody{
        overflow:auto;
        position: relative;
    }
</style>
@section('content')
    <div class="row chat-row">
        <div class="col-md-3 col-xs-3 col-sm-3">
            <div class="users">
                <h5>Users</h5>

                <ul class="list-group list-chat-item">
                    @if($users->count())
                        @foreach($users as $user)
                            <li class="chat-user-list
                                @if($user->id == $friendInfo->id) active @endif">
                                <a href="{{ route('message.conversation', $user->id) }}">
                                   

                                    <div class="chat-name font-weight-bold text-black">
                                        <span class="user-status-icon user-icon-{{ $user->id }}">{{ $user->name }}</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    @endif
                </ul>
            </div>

           

        </div>

        <div class="col-md-9 col-xs-9 col-sm-9 chat-section">
            <div class="chat-header">
                <div class="chat-image">
                   
                </div>

                <div class="chat-name font-weight-bold">
                    {{ $user->name }}
                    <i class="fa fa-circle user-status-head" title="away"
                       id="userStatusHead{{$friendInfo->id}}"></i>
                </div>
            </div>

            <div class="chat-body" id="chatBody">
                <div class="message-listing" id="messageWrapper">
                    <?php if($messages){ ?>
                    @foreach($messages as $key=>$val)
                      @if($val['position']=='left')
                      <div class="message">
                          <div>
                          {{$val['message']}}
                          </div>
                    
                      </div>
                      @else
                      <div style="text-align:right" class="message">
                      <div>
                      {{$val['message']}}
                    </div>
                      </div>
                      @endif
                    

                    @endforeach
                    <?php } else{ ?>
                         <p>no messages...</p>
                        <?php } ?>
                </div>
            </div>

            <div class="chat-box">
                <div class="chat-input bg-white" id="chatInput" contenteditable="">

                </div>

               
            </div>
        </div>
    </div>

    
@endsection

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" integrity="sha512-nMNlpuaDPrqlEls3IX/Q56H36qvBASwb3ipuo3MxeWbsQB1881ox0cRv7UPTgBlriqoynt35KjEwgGUeUXIPnw==" crossorigin="anonymous" />
@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js" integrity="sha512-2ImtlRlf2VVmiGZsjm9bEyhjGW4dU7B6TNwh/hx/iSByxNENtj3WVE6o/9Lj4TJeVXPi4bnOIMXFIJJAeufa0A==" crossorigin="anonymous"></script>
    <script>

        function scrollToBottom(){
            var messageBody = document.querySelector('#chatBody');
             messageBody.scrollTop = messageBody.scrollHeight - messageBody.clientHeight;
        }
        $(function (){
            scrollToBottom();

           

            let $chatInput = $(".chat-input");
            let $chatInputToolbar = $(".chat-input-toolbar");
            let $chatBody = $(".chat-body");
            let $messageWrapper = $("#messageWrapper");


            let user_id = "{{ auth()->user()->id }}";
            let ip_address = '127.0.0.1';
            let socket_port = '8005';
            let socket = io(ip_address + ':' + socket_port);
            let friendId = "{{ $friendInfo->id }}";

            socket.on('connect', function() {
                socket.emit('user_connected', user_id);
            });

            socket.on('updateUserStatus', (data) => {
                let $userStatusIcon = $('.user-status-icon');
                $userStatusIcon.removeClass('text-success');
                $userStatusIcon.attr('title', 'Away');

                $.each(data, function (key, val) {
                    if (val !== null && val !== 0) {
                        let $userIcon = $(".user-icon-"+key);
                        $userIcon.addClass('text-success');
                        $userIcon.attr('title','Online');
                    }
                });
            });

            $chatInput.keypress(function (e) {
               let message = $(this).html();
               if (e.which === 13 && !e.shiftKey) {
                   $chatInput.html("");
                   sendMessage(message);
                   scrollToBottom()
                   return false;
               }
            });

            function sendMessage(message) {
                let url = "{{ route('message.send-message') }}";
                let form = $(this);
                let formData = new FormData();
                let token = "{{ csrf_token() }}";

                formData.append('message', message);
                formData.append('_token', token);
                formData.append('receiver_id', friendId);

                appendMessageToSender(message);

                $.ajax({
                   url: url,
                   type: 'POST',
                   data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                   success: function (response) {
                       if (response.success) {
                          // console.log(response.data);
                       }
                   }
                });
            }

            function appendMessageToSender(message) {
                let name = '{{ $myInfo->name }}';
                let image = '';
                 
                var div = '<div class="message" style="text-align:right"><div>'+message+'</div></div>';
                

                $messageWrapper.append(div);
            }

            function appendMessageToReceiver(message) {
                let name = '{{ $friendInfo->name }}';
                let image = '';
                var div = '<div class="message" style="text-align:left"><div>'+message.content+'</div></div>';
                

                $messageWrapper.append(div);
            }

            socket.on("private-channel:App\\Events\\PrivateMessageEvent", function (message)
            {
               appendMessageToReceiver(message);
            });

            let $addGroupModal = $("#addGroupModal");
            $(document).on("click", ".btn-add-group", function (){
                $addGroupModal.modal();
            });

            $("#selectMember").select2();
        });
    </script>
@endpush
