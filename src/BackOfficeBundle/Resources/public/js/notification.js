$(document).ready(function(){
  var ws = new SockJS('http://localhost:*******/stomp');
  var client = Stomp.over(ws);

  client.heartbeat.outgoing = 0;
  client.heartbeat.incoming = 0;

  var onDebug = function(m) {
    console.log('DEBUG', m);
  };

  var onConnect = function() {
    client.subscribe('/exchange/notify_users', function(d) {
      var message_obj = JSON.parse(d.body);
      $.notify(message_obj.message, { globalPosition: 'bottom right', autoHide: true, autoHideDelay: 100000 });
    });
  };

  var onError = function(e) {
    console.log('ERROR', e);
  };

  client.debug = onDebug;
  client.connect('*******', '*******', onConnect, onError, '/');
});
