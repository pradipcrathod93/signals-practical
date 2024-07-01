<?php
session_start();

// Check if action is set
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'start':
            if (isset($_POST['sequence']) && isset($_POST['greenInterval']) && isset($_POST['yellowInterval'])) {
                $sequence = strtoupper($_POST['sequence']);
                $greenInterval = intval($_POST['greenInterval']);
                $yellowInterval = intval($_POST['yellowInterval']);

                // Validate sequence (must be exactly 4 characters)
                if (strlen($sequence) !== 4 || !preg_match('/^[A-D]+$/', $sequence)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid sequence format']);
                    exit;
                }

                // Validate intervals (must be positive)
                if ($greenInterval <= 0 || $yellowInterval <= 0) {
                    echo json_encode(['status' => 'error', 'message' => 'Intervals must be positive']);
                    exit;
                }

                // Initialize session variables for signals A, B, C, D
                $_SESSION['sequence'] = str_split($sequence);
                $_SESSION['greenInterval'] = $greenInterval;
                $_SESSION['yellowInterval'] = $yellowInterval;
                $_SESSION['currentSignalIndex'] = 0; // Start with the first signal in the sequence

                // Initialize signal states as red and timers as 0
                $_SESSION['signalStates'] = array_fill(0, 4, 'red');
                $_SESSION['timers'] = array_fill(0, 4, ['green' => 0, 'yellow' => 0]);

                echo json_encode(['status' => 'success', 'message' => 'Signal sequence started']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
            }
            break;

        case 'stop':
            session_unset();
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Signal sequence stopped']);
            break;

        case 'getSignalState':
            if (isset($_SESSION['sequence']) && isset($_SESSION['greenInterval']) && isset($_SESSION['yellowInterval']) && isset($_SESSION['currentSignalIndex']) && isset($_SESSION['signalStates']) && isset($_SESSION['timers'])) {
                $sequence = $_SESSION['sequence'];
                $greenInterval = $_SESSION['greenInterval'];
                $yellowInterval = $_SESSION['yellowInterval'];
                $currentSignalIndex = $_SESSION['currentSignalIndex'];
                $signalStates = $_SESSION['signalStates'];
                $timers = $_SESSION['timers'];

                // Update current signal state based on intervals
                $currentSignal = $sequence[$currentSignalIndex];
                switch ($signalStates[$currentSignalIndex]) {
                    case 'red':
                        $signalStates[$currentSignalIndex] = 'green';
                        $timers[$currentSignalIndex]['green'] = 1; // Start green timer
                        break;
                    case 'green':
                        if ($timers[$currentSignalIndex]['green'] >= $greenInterval) {
                            $signalStates[$currentSignalIndex] = 'yellow';
                            $timers[$currentSignalIndex]['green'] = 0; // Reset green timer
                            $timers[$currentSignalIndex]['yellow'] = 1; // Start yellow timer
                        } else {
                            $timers[$currentSignalIndex]['green']++; // Increment green timer
                        }
                        break;
                    case 'yellow':
                        if ($timers[$currentSignalIndex]['yellow'] >= $yellowInterval) {
                            $signalStates[$currentSignalIndex] = 'red';
                            $timers[$currentSignalIndex]['yellow'] = 0; // Reset yellow timer

                            // Move to the next signal in the sequence
                            $_SESSION['currentSignalIndex'] = ($currentSignalIndex + 1) % 4; // Wrap around for ABCD
                        } else {
                            $timers[$currentSignalIndex]['yellow']++; // Increment yellow timer
                        }
                        break;
                    default:
                        break;
                }

                // Save updated states and timers back to session
                $_SESSION['signalStates'] = $signalStates;
                $_SESSION['timers'] = $timers;

                echo json_encode(['status' => 'success', 'states' => $signalStates, 'timers' => $timers]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Signal sequence not started']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Action not specified']);
}
?>
