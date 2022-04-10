jQuery(document).ready(function($) {


    /**
     * Socketstuff
     */
    //let urlen = "http://188.150.180.106:3000";
    //let urlen = "http://192.168.0.11:3000";
    //var urlen = "http://localhost:3000/";
    //var urlen = "http://qwad.se:51237";
    //var urlen = "https://robotcar-271510.appspot.com";
    let urlen = "http://robot.qwad.se";

    var connection_attempts = 0;
    var socket = io(urlen, {
        reconnectionAttempts: 1,
        /* path: "/robotbil/" */
    });

    $.post(
        page_info.ajax_url, { "action": "snillrik_fetchsessontoken" },
        function(response) {
            let snillrik_robottoken = response;
            socket = io.connect(urlen, {
                reconnectionAttempts: 3,
                /* path: "/robotbil/" */
            });
            socket.on('reconnecting', () => {
                connection_attempts++;
                $("#snillrik_connecting_status").html("Timed out, trying again (" + connection_attempts + ")");
            });
            socket.on('reconnect_failed', () => {
                $("#snillrik_connecting_status").html("Connection failed");
                $("#snillrik_connecting_status").css({ "color": "red" });
                console.log("wat, noo connexioon tried " + connection_attempts + " times... wth... #sadface");
                connection_attempts = 0;
            });
            socket.on('connect', () => {
                socket.emit('wanting_robot_token', snillrik_robottoken);
                $("#snillrik_connecting_status").html("Connected");
                $("#snillrik_connecting_status").css({ "color": "green" });
            });

        }
    );

    $("#snillrik_connecting_status").css({ "color": "gray" });

    /**
     * Gamepad stuff
     */

    var sessiontoken = "secretasf**k";
    var speed = 0;
    var turn = 0;
    var controller_axis_and_buttons;
    var controller_axis_prev_val = [0, 0, 0, 0];

    var haveEvents = 'ongamepadconnected' in window;
    var controllers = {};

    function connecthandler(e) {
        console.log(e);
        if ($("#snillrik_controller_axis_and_buttons").length > 0)
            addgamepad(e.gamepad);
    }

    function addgamepad(gamepad) {
        controllers[gamepad.index] = gamepad;
        controller_axis_and_buttons = JSON.parse($("#snillrik_controller_axis_and_buttons").val() == "" ? "{}" : $("#snillrik_controller_axis_and_buttons").val());
        $.post(
            page_info.ajax_url, {
                "action": "snillrik_fetchsessontoken"
            },
            function(response) {
                sessiontoken = response;
                console.log(response);
            }
        );
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
                let is_set_str = controller_axis_and_buttons["axis_" + i] == undefined ? "" : controller_axis_and_buttons["axis_" + i];
                let is_procset_str = controller_axis_and_buttons["axisproc_" + i] == undefined ? 1 : parseInt(controller_axis_and_buttons["axisproc_" + i]) / 100;
                text_out += is_set_str + " " + (controller.axes[i] * is_procset_str).toFixed(4) + "<br />";

                if (controller_axis_prev_val[i] != controller.axes[i] && is_set_str != "") {
                    console.log("command: " + is_set_str);
                    socket.emit("controller_command", { "command": is_set_str, "value": controller.axes[i] * is_procset_str });
                    //socket.emit(is_set_str, controller.axes[i]*is_procset_str);
                    controller_axis_prev_val[i] = controller.axes[i];
                }

            }

            for (i = 0; i < controller.buttons.length; i++) {
                var val = controller.buttons[i];
                let is_set_str = controller_axis_and_buttons["button_" + i] == undefined ? "" : controller_axis_and_buttons["button_" + i];
                var pressed = val == 1.0;
                if (typeof(val) == "object" && is_set_str != "") {
                    pressed = val.pressed;
                    val = val.value;
                    text_out += is_set_str + pressed + "  " + val + "<br />";
                }
            }

            $("#snillrik_controller_text").html(text_out);
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
    $(".snillrik_robot_buttons").on("click", "a", function(e) {
        e.preventDefault();
        let robot_action = $(this).attr("id").split("_")[2];

        var data = {
            "action": "snillrik_call_robot",
            "robot_action": robot_action
        };

        $.post(
            page_info.ajax_url,
            data,
            function(response) {
                console.log(response);
            }
        );

    });

});