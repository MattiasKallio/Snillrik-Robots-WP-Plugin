jQuery(document).ready(function ($) {
  var hasgamepad_div = $("#snillrik_robot_gamepad_info").lenght != 0;
  if (hasgamepad_div) {
    var haveEvents = 'ongamepadconnected' in window;
    var controllers = {};

    //connect gamepad
    function connecthandler(e) {
      console.log(e);
      addgamepad(e.gamepad);
    }

    function addgamepad(gamepad) {
      controllers[gamepad.index] = gamepad;
      let buttons_num = gamepad.buttons.length;
      let axes_num = gamepad.axes.length;
      //console.log(gamepad.id + buttons_num + " " + axes_num);
      let saved_array = $("#snillrik_controller_axis_and_buttons").val()=="" 
        ? "{}" 
        : JSON.parse($("#snillrik_buttons_and_axis").val());
      let str_out = "<table><tr><th>Name</th><th>Action</th><th>Procent</th></tr>";
      console.log(saved_array);
      for(var i=0;i<axes_num;i++){
        let is_set_str = saved_array["axis_"+i]==undefined ? "" : saved_array["axis_"+i];
        let is_proc_str = saved_array["axisproc_"+i]==undefined ? "" : saved_array["axisproc_"+i];
        str_out += "<tr><td><strong>Axis"+i+"</strong></td><td><input type='text' name='snillrik_gamepadins[]' id='axis_"+i+"' value='"+is_set_str+"' /></td><td><input type='text' name='snillrik_gamepadins[]' id='axisproc_"+i+"' value='"+is_proc_str+"' /></td></tr>";
        //$("#snillrik_robot_gamepad_info").append("<tr><td><strong>Axis"+i+"</strong></td><td><input type='text' name='snillrik_gamepadins[]' id='axis_"+i+"' value='"+is_set_str+"' /></td><td><input type='text' name='snillrik_gamepadins[]' id='axisproc_"+i+"' value='"+is_proc_str+"' /></td></tr>");
      }
      for(var i=0;i<buttons_num;i++){
        let is_set_str = saved_array["button_"+i]==undefined ? "" : saved_array["button_"+i];
        let is_proc_str = saved_array["buttonproc_"+i]==undefined ? "" : saved_array["buttonproc_"+i];
        str_out += "<tr><td><strong>Button "+i+"</strong></td><td><input type='text' name='snillrik_gamepadins[]' id='button_"+i+"' value='"+is_set_str+"' /></td><td><input type='text' name='snillrik_gamepadins[]' id='buttonproc_"+i+"' value='"+is_proc_str+"' /></td></tr>";
        //$("#snillrik_robot_gamepad_info").append("<tr><td><strong>Button "+i+"</strong></td><td><input type='text' name='snillrik_gamepadins[]' id='button_"+i+"' value='"+is_set_str+"' /></td><td><input type='text' name='snillrik_gamepadins[]' id='buttonproc_"+i+"' value='"+is_proc_str+"' /></td></tr>");    
      }
      $("#snillrik_robot_gamepad_info").html(str_out+"</table>");
      requestAnimationFrame(updateStatus);
    }

    function updateStatus() {
      if (!haveEvents) {
        scangamepads();
      }
      var i = 0;
      var j;
  
      for (j in controllers) {
        var controller = controllers[j];
  
        let text_out = "";
        
        for (i = 0; i < controller.axes.length; i++) {
          if(controller.axes[i]>0.1 || controller.axes[i]<-0.1){
            console.log("moving "+ i);
            $("#axis_"+i).css({background:"#ffd6d6"});
          }
          else{
            $("#axis_"+i).css({background:"#fff"});
          }
          //text_out += controller.axes[i].toFixed(4) + "<br />";
        }
       
        for (i = 0; i < controller.buttons.length; i++) {
          var val = controller.buttons[i];
          var pressed = val == 1.0;
          if (typeof(val) == "object") {
            pressed = val.pressed;
            val = val.value;
            if(pressed)
              $("#button_"+i).css({background:"#ffd6d6"});
            else
              $("#button_"+i).css({background:"#fff"});
          }
        }
        
        $("#snillrik_controller_text").html(text_out);
      }
  
      requestAnimationFrame(updateStatus);
    }    

    function disconnecthandler(e) {
      removegamepad(e.gamepad);
    }

    function scangamepads() {
      var gamepads = navigator.getGamepads ? navigator.getGamepads() : (navigator.webkitGetGamepads ? navigator.webkitGetGamepads() : []);
      for (var i = 0; i < gamepads.length; i++) {
        if (gamepads[i]) {
          if (gamepads[i].index in controllers) {
            controllers[gamepads[i].index] = gamepads[i];
          } else {
            addgamepad(gamepads[i]);
          }
        }
      }
    }

    window.addEventListener("gamepadconnected", connecthandler);
    window.addEventListener("gamepaddisconnected", disconnecthandler);

    if (!haveEvents) {
      setInterval(scangamepads, 500);
    }
    $("#buttons_and_axis_save").on("click", function(){
      
        //var buttons_and_axes_arr = $("#snillrik_robot_gamepad_info input");
        let array_out = {}
        $('input[name="snillrik_gamepadins[]"]').each(function(i){
          if($(this).val()!=""){
            array_out[$(this).attr("id")]=$(this).val();
          }
        });
          
        $("#snillrik_buttons_and_axis").val(JSON.stringify(array_out));
    });
  }

});