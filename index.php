<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Signal Light Control</title>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<style>
    .signal-circle {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: inline-block;
        margin: 10px;
        text-align: center;
        line-height: 100px;
        font-size: 20px;
        background-color: red; /* Default color is red */
        color: white;
    }
    .signal-green {
        background-color: green !important; /* Override with green for green state */
    }
    .signal-yellow {
        background-color: yellow !important; /* Override with yellow for yellow state */
    }
    .timer {
        font-size: 12px;
        margin-top: 5px;
    }
</style>
<script>
$(document).ready(function() {
    var intervalId = null; // Variable to hold the interval ID for updates
    var sequence = ''; // Variable to store the selected sequence

    $('#sequenceInput').change(function() {
        sequence = $(this).val(); // Update sequence variable when selection changes
    });

    $('#startButton').click(function() {
        var greenInterval = parseInt($('#greenInterval').val()); // Get green interval in seconds
        var yellowInterval = parseInt($('#yellowInterval').val()); // Get yellow interval in seconds

        if (sequence.length !== 4) {
            alert('Sequence must be exactly 4 characters (e.g., ABCD)');
            return;
        }

        if (greenInterval <= 0 || yellowInterval <= 0) {
            alert('Intervals must be positive');
            return;
        }

        $.ajax({
            type: 'POST',
            url: 'server.php',
            data: {
                action: 'start',
                sequence: sequence,
                greenInterval: greenInterval,
                yellowInterval: yellowInterval
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    startSignalSequence();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error communicating with server');
            }
        });
    });

    $('#stopButton').click(function() {
        $.ajax({
            type: 'POST',
            url: 'server.php',
            data: {
                action: 'stop'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    stopSignalSequence();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error communicating with server');
            }
        });
    });

    function startSignalSequence() {
        clearInterval(intervalId); // Clear any existing interval

        // Initial update
        updateSignalState();

        // Set interval to update signal state
        intervalId = setInterval(function() {
            updateSignalState();
        }, 1000); // Check every second for updates
    }

    function stopSignalSequence() {
        clearInterval(intervalId); // Stop the interval updates
        $('.signal-circle').removeClass('signal-green signal-yellow').addClass('signal-red');
        $('.timer').text('');
    }

    function updateSignalState() {
        $.ajax({
            type: 'POST',
            url: 'server.php',
            data: {
                action: 'getSignalState'
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    var states = response.states;
                    var timers = response.timers;

                    // Reset all signals to red and clear timers
                    $('.signal-circle').removeClass('signal-green signal-yellow').addClass('signal-red');
                    $('.timer').text('');

                    // Update each signal based on its state (green or yellow) and display timers
                    states.forEach(function(state, index) {
                        var signalId = '#signal' + sequence[index]; // Get signal ID based on sequence order
                        if (state === 'green') {
                            $(signalId).removeClass('signal-red signal-yellow').addClass('signal-green');
                            $(signalId + 'Timer').text('Green Timer: ' + timers[index].green + 's');
                        } else if (state === 'yellow') {
                            $(signalId).removeClass('signal-red signal-green').addClass('signal-yellow');
                            $(signalId + 'Timer').text('Yellow Timer: ' + timers[index].yellow + 's');
                        }
                    });
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Error communicating with server');
            }
        });
    }
});
</script>
</head>
<body>
    <h2>Signal Light Control</h2>
    <div id="signalA" class="signal-circle">A <span id="signalATimer" class="timer"></span></div>
    <div id="signalB" class="signal-circle">B <span id="signalBTimer" class="timer"></span></div>
    <div id="signalC" class="signal-circle">C <span id="signalCTimer" class="timer"></span></div>
    <div id="signalD" class="signal-circle">D <span id="signalDTimer" class="timer"></span></div><br><br>

    <label for="sequenceInput">Sequence:</label>
    <select id="sequenceInput">
        <option value="ABCD">ABCD</option>
        <option value="BCDA">BCDA</option>
        <option value="CDAB">CDAB</option>
        <option value="DABC">DABC</option>
    </select><br><br>

    <label for="greenInterval">Green Interval (seconds):</label>
    <input type="number" id="greenInterval" name="greenInterval" required><br><br>

    <label for="yellowInterval">Yellow Interval (seconds):</label>
    <input type="number" id="yellowInterval" name="yellowInterval" required><br><br>

    <button id="startButton">Start Signals</button>
    <button id="stopButton">Stop Signals</button>
</body>
</html>
