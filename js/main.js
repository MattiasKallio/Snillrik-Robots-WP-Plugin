jQuery(document).ready(function ($) {


    /**
     * Socketstuff
     */

    //let urlen = "ws://192.168.0.34/ws";
    let ipnumber = $("#snillrik_robot_ip").val();
    let urlen = "ws://" + ipnumber + "/ws";
    let is_connected = false;

    var robottsocket; // = new WebSocket(urlen, "protocolOne");
    connect();
    var heartbeat_msg = JSON.stringify({ "command": 'am_i_alive', "value": true }), heartbeat_interval = null, missed_heartbeats = 0;
    console.log(robottsocket);


    function connect() {
        is_connected = false;
        robottsocket = new WebSocket(urlen, "protocolOne");
        robottsocket.onopen = function (event) {
            console.log("onopening connection to socket");
            is_connected = true;
            $("#snillrik_connecting_status").html("Connected");
            $("#snillrik_connecting_status").addClass("snillrik_connected");

            //Check if still connected every 3 seconds
            if (heartbeat_interval === null) {
                missed_heartbeats = 0;
                heartbeat_interval = setInterval(function () {
                    try {
                        missed_heartbeats++;
                        if (missed_heartbeats >= 3)
                            throw new Error("Too many missed heartbeats.");
                        robottsocket.send(heartbeat_msg);
                    } catch (e) {
                        clearInterval(heartbeat_interval);
                        heartbeat_interval = null;
                        console.warn("Closing connection. Reason: " + e.message);
                        $("#snillrik_connecting_status").html("Disconnected");
                        $("#snillrik_connecting_status").removeClass("snillrik_connected");
                        robottsocket.close();
                        //setTimeout(connect, 2000);
                    }
                }, 3000);
            }
        }


        //For onclose event
        robottsocket.onclose = function (event) {
            console.log('Connection closed');
            is_connected = false;
            $("#snillrik_connecting_status").html("Discontected");
            $("#snillrik_connecting_status").removeClass("snillrik_connected");
            setTimeout(connect, 2000);
        }

        //For receiving messages from the server
        robottsocket.onmessage = function (event) {
            //console.log("Message received");
            missed_heartbeats = 0;
            if (event.data.indexOf("ipnumber") > -1) {
                let ipnumber = event.data.split("ipnumber:")[1];
                $("#ipnumber").html(ipnumber);
            }
            else if (event.data === "alive") {
                // reset the counter for missed heartbeats
                missed_heartbeats = 0;
                return;
            }
            else { //json data
                let data = JSON.parse(event.data);
                //{"yaw":10,"carspeed":174,"rotation_direction":"left"}
                $("#snillrik_controller_text").html("Yaw: " + data.motors_info.yaw 
                + "<br />Hastighet (0-250)" + data.motors_info.carspeed 
                + "<br />Rotation :" + (data.motors_info.rotation_direction == "left" ? "Vänster" : "Höger")
                + "<br />Sonar: " + data.sonar_info.distance);
                //console.log(data);
            }
        }
    }
    /**
     * Gamepad stuff
     */

    var sessiontoken = "secretasf**k";
    var the_timer = new Date();
    var last_sent = the_timer.getTime();
    var speed = 0;
    var turn = 0;
    var controller_axis_and_buttons;
    var controller_axis_prev_val = [0, 0, 0, 0];

    var haveEvents = 'ongamepadconnected' in window;
    var controllers = {};

    //Gamepad is conneted
    function connecthandler(e) {
        console.log(e);
        if ($("#snillrik_controller_axis_and_buttons").length > 0)
            addgamepad(e.gamepad);
    }

    //Adding gamepad
    function addgamepad(gamepad) {
        controllers[gamepad.index] = gamepad;
        controller_axis_and_buttons = JSON.parse($("#snillrik_controller_axis_and_buttons").val() == "" ? "{}" : $("#snillrik_controller_axis_and_buttons").val());
        $.post(
            page_info.ajax_url, {
            "action": "snillrik_fetchsessontoken"
        },
            function (response) {
                sessiontoken = response;
                console.log(response);
            }
        );
        requestAnimationFrame(updateStatus);
    }

    //Gamepad status update
    function updateStatus() {
        if (!haveEvents) {
            scangamepads();
        }
        var i = 0;
        var j;

        for (j in controllers) {
            var controller = controllers[j];
            let text_out = "";

            //For the axes
            for (i = 0; i < controller.axes.length; i++) {
                //get the value of the axis
                let is_set_str = controller_axis_and_buttons["axis_" + i] == undefined
                    ? ""
                    : controller_axis_and_buttons["axis_" + i];
                //get value of the axis procent 
                let is_procset_str = controller_axis_and_buttons["axisproc_" + i] == undefined
                    ? 1 :
                    parseInt(controller_axis_and_buttons["axisproc_" + i]) / 100;
                //set the text for output
                text_out += is_set_str + " " + (controller.axes[i] * is_procset_str).toFixed(4) + "<br />";

                if (controller_axis_prev_val[i] != controller.axes[i] && is_set_str != "" && is_connected) {

                    let sendstring = { "command": is_set_str, "value": controller.axes[i] * is_procset_str };
                    the_timer = new Date();
                    if (the_timer.getTime() - last_sent > 100 && Math.abs(controller.axes[i] * is_procset_str) > 0.01) {
                        //console.log(sendstring);
                        robottsocket.send(JSON.stringify(sendstring));
                        controller_axis_prev_val[i] = controller.axes[i];
                        last_sent = the_timer.getTime();
                    }
                }
            }

            //For the buttons
            for (i = 0; i < controller.buttons.length; i++) {
                var val = controller.buttons[i];
                let is_set_str = controller_axis_and_buttons["button_" + i] == undefined ? "" : controller_axis_and_buttons["button_" + i];
                var pressed = val == 1.0;
                if (typeof (val) == "object" && is_set_str != "") {
                    pressed = val.pressed;
                    if (pressed) {
                        //console.log(is_set_str + " " + val.value);
                        val = val.value;

                        let sendstring = { "command": is_set_str, "value": val };
                        //robottsocket.send(JSON.stringify(sendstring));

                        //text_out += is_set_str + pressed + "  " + val + "<br />";
                        the_timer = new Date();
                        if (the_timer.getTime() - last_sent > 300) {
                            //console.log(sendstring);
                            robottsocket.send(JSON.stringify(sendstring));
                            controller_axis_prev_val[i] = controller.axes[i];
                            last_sent = the_timer.getTime();
                        }
                    }
                }
            }


            //$("#snillrik_controller_text").html(text_out);
        }

        requestAnimationFrame(updateStatus);
    }

    function disconnecthandler(e) {
        removegamepad(e.gamepad);
    }

    function removegamepad(gamepad) {
        delete controllers[gamepad.index];
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

    var video = document.querySelector("#videoElement");
    /* 
      if (navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: true })
          .then(function (stream) {
            video.srcObject = stream;
          })
          .catch(function (err0r) {
            console.log("Something went wrong!");
          });
      } */

    /**
     * Old stuff, for buttons on page. might make a comeback.
     */
    $(".snillrik_robot_buttons").on("click", "a", function (e) {
        e.preventDefault();
        let robot_action = $(this).attr("id").split("_")[2];

        var data = {
            "action": "snillrik_call_robot",
            "robot_action": robot_action
        };

        $.post(
            page_info.ajax_url,
            data,
            function (response) {
                console.log(response);
            }
        );

    });

});