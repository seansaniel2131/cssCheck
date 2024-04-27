<?php
session_start();
include './APIs/config.php';

// Check if patient is logged in
if (!isset($_SESSION['patient_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.html");
    exit();
}

// Sanitize input to prevent SQL injection
$patientID = $conn->real_escape_string($_SESSION['patient_id']);

// Retrieve the patient's first name and last name
$sql = "SELECT FirstName, LastName FROM Patients WHERE PatientID = $patientID";
$result = $conn->query($sql);

$fullName = '';
if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $fullName = $row['FirstName'] . ' ' . $row['LastName'];
}

// Fetch branches
$sql = "SELECT * FROM Branches";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <link rel="stylesheet" href="./CSS/patient_dashboard.css">
</head>
<body>
    <?php include './Misc/nav.html'; ?>
    <h2>Welcome to the Dashboard</h2>
    <?php if ($fullName): ?>
        <p>Hello, <?php echo $fullName; ?></p>
    <?php else: ?>
        <p>Welcome</p>
    <?php endif; ?>
    <p>Your Patient ID: <?php echo $_SESSION['patient_id']; ?></p>

    <h3>Select a Branch:</h3>
    <form id="branchForm">
        <label for="branchSelect">Select a Branch:</label>
        <select id="branchSelect" name="branch">
            <?php if ($result->num_rows > 0): ?>
                <?php $firstBranch = true; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['BranchID']; ?>" <?php if ($firstBranch) { echo 'selected'; $firstBranch = false; } ?>>
                        <?php echo $row['BranchName'] . ' - ' . $row['BranchLocation']; ?>
                    </option>
                <?php endwhile; ?>
            <?php else: ?>
                <option value="" selected>No branches available</option> <!-- Set default option as selected -->
            <?php endif; ?>
        </select>
    </form>
    <div id="navigation">
        <button id="toggleView">Toggle View</button>
        <button id="prev">Previous</button>
        <button id="next">Next</button>
    </div>
    <h2 id="calendarTitle">Calendar</h2>
    <div id="calendarContainer"></div>
    
    <!-- Appointment request form container -->
    <div id="appointmentFormContainer" class="hidden">
        <div id="popupForm">
            <h2>Appointment Request</h2>
            <form id="appointmentForm">
                <label for="time">Time:</label>
                <input type="text" id="time" name="time" readonly><br><br>
                <label for="date">Date:</label>
                <input type="text" id="date" name="date" readonly><br><br>
                <button type="submit">Confirm</button>
                <button type="button" onclick="closeAppointmentForm()">Cancel</button>
            </form>
        </div>
    </div>
    
    <div id="appointmentsTableContainer">
        <h2>My Appointments</h2>
        <table id="appointmentsTable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Appointments will be dynamically inserted here -->
            </tbody>
        </table>
    </div>
    
    <!-- Edit Appointment Modal Container -->
    <div id="editAppointmentModal" class="modal hidden">
        <div class="modal-content">
            <h2>Edit Appointment</h2>
            <form id="editAppointmentForm">
                <label for="editTime">Time:</label>
                <input type="text" id="editTime" name="editTime" readonly><br><br>
                <label for="editDate">Date:</label>
                <input type="text" id="editDate" name="editDate" readonly><br><br>
                <button type="submit">Save Changes</button>
                <button type="button" onclick="closeEditAppointmentModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/dayjs@1.10.7/dayjs.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Define timeSlots globally with 30-minute intervals from 8:00 AM to 6:00 PM
        var timeSlots = [];
        for (var i = 8; i < 18; i++) {
            timeSlots.push(i.toString().padStart(2, '0') + ':00');
            timeSlots.push(i.toString().padStart(2, '0') + ':30');
        }

        var currentDate = dayjs(); // Current date
        var selectedView = 'day'; // Default view is day
        var selectedBranchID = 1; // Declare selectedBranchID and set it to 1 initially

        // Set the default branch ID to 1 when the page initially loads
        var branchSelect = document.getElementById('branchSelect');
        if (branchSelect) {
            selectedBranchID = branchSelect.value || 1;
        }

        // Fetch appointments for the default branch and current date
        fetchAppointments(selectedBranchID, currentDate.format('YYYY-MM-DD'));

        // Initial render
        renderCalendar();

        // Function to render the calendar based on the selected view
        function renderCalendar() {
            var calendarContainer = document.getElementById('calendarContainer');
            calendarContainer.innerHTML = ''; // Clear previous content

            if (selectedView === 'day') {
                renderDailyView(calendarContainer);
            } else if (selectedView === 'month') {
                renderMonthlyView(calendarContainer);
            }
        }

// Function to render the daily view
function renderDailyView(container) {
    console.log("Rendering daily view...");

    // Display current day's date
    document.getElementById('calendarTitle').textContent = currentDate.format('dddd, MMMM D, YYYY');

    // Create table element
    var table = document.createElement('table');
    console.log("Table created:", table);

    // Create table body for appointments
    var tbody = document.createElement('tbody');
    console.log("Table body created:", tbody);

    // Add event listener to the tbody element to handle click events on data cells
    tbody.addEventListener('click', function(event) {
        console.log("Click event triggered on tbody");
        var targetCell = event.target.closest('td');
        if (!targetCell) return; // Exit if the clicked element is not a <td> cell

        var timeSlot = targetCell.parentElement.cells[0].textContent; // Get the time slot from the previous cell
        var appointmentDate = currentDate.format('YYYY-MM-DD');
        var appointmentData = targetCell.textContent.trim(); // Get the appointment data from the clicked cell
        if (appointmentData === '') { // Check if the cell is empty (not booked)
            console.log("Selected time:", timeSlot);
            console.log("Selected date:", appointmentDate);
            displayAppointmentRequestForm(timeSlot, appointmentDate);
        } else {
            console.log("Cell already booked.");
            // Optionally, you can display a message to the user indicating that the selected time is already booked.
        }
    });

    // Loop through time slots to create table rows
    for (var i = 0; i < timeSlots.length; i++) {
        var tr = document.createElement('tr');

        // Create cell for time slot
        var timeSlotTd = document.createElement('td');
        timeSlotTd.textContent = timeSlots[i];
        tr.appendChild(timeSlotTd);

        // Create cell for appointment data
        var dataTd = document.createElement('td');
        dataTd.textContent = ''; // Leave appointment data cell empty initially
        tr.appendChild(dataTd);

        tbody.appendChild(tr);
    }

    table.appendChild(tbody);
    console.log("Table body appended to table:", tbody);

    container.appendChild(table);
    console.log("Table appended to container:", container);

    // Fetch appointments for the current day and selected branch
    fetchAppointments(selectedBranchID, currentDate.format('YYYY-MM-DD'));
}

// Function to display the appointment request form
function displayAppointmentRequestForm(timeSlot, appointmentDate) {
    // Create the form
    var appointmentForm = document.createElement('form');
    appointmentForm.id = 'appointmentForm';

    // Create hidden input fields for time, date, and patientID
    var timeInput = document.createElement('input');
    timeInput.type = 'hidden';
    timeInput.name = 'time';
    timeInput.value = timeSlot;

    var dateInput = document.createElement('input');
    dateInput.type = 'hidden';
    dateInput.name = 'date';
    dateInput.value = appointmentDate;

    var patientIDInput = document.createElement('input');
    patientIDInput.type = 'hidden';
    patientIDInput.name = 'patientID';
    patientIDInput.value = '<?php echo $_SESSION['patient_id']; ?>';

    // Retrieve the branch ID from the selected branch in the dropdown menu
    var branchSelect = document.getElementById('branchSelect');
    var branchID = branchSelect.value; // Assuming the value of the option is the branch ID

    // Create a hidden input field for branchID
    var branchIDInput = document.createElement('input');
    branchIDInput.type = 'hidden';
    branchIDInput.name = 'branchID';
    branchIDInput.value = branchID;

    // Create read-only input fields for time and date to display in the form
    var timeDisplay = document.createElement('input');
    timeDisplay.type = 'text';
    timeDisplay.name = 'time_display';
    timeDisplay.value = timeSlot;
    timeDisplay.readOnly = true;

    var dateDisplay = document.createElement('input');
    dateDisplay.type = 'text';
    dateDisplay.name = 'date_display';
    dateDisplay.value = appointmentDate;
    dateDisplay.readOnly = true;

    // Create a submit button
    var submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.textContent = 'Confirm';

    // Create a close button
    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.textContent = 'Close';
    closeButton.addEventListener('click', function() {
        closeModal(); // Close the modal
    });

    // Append input fields, submit button, and close button to the form
    appointmentForm.appendChild(timeInput);
    appointmentForm.appendChild(dateInput);
    appointmentForm.appendChild(patientIDInput); // Include patientID input field
    appointmentForm.appendChild(branchIDInput); // Include branchID input field
    appointmentForm.appendChild(timeDisplay);
    appointmentForm.appendChild(dateDisplay);
    appointmentForm.appendChild(submitButton);

    // Add event listener to the form for form submission
    appointmentForm.addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission
        var formData = new FormData(appointmentForm);
        submitAppointment(formData);
        closeModal(); // Close the modal after submission
    });

    // Display the form inside a modal
    showModal(appointmentForm);
}


// Function to submit appointment data
function submitAppointment(formData) {
    // Convert formData to an object
    const data = Object.fromEntries(formData);

    // Log the data being submitted
    console.log("Submitting appointment data:", data);

    // Convert data object to a query string
    const queryString = Object.entries(data).map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`).join('&');

    // Log the generated query string
    console.log("Query string:", queryString);

    // Send the request with the generated query string
    fetch(`./APIs/submit_appointments.php?${queryString}`, {
        method: 'POST',
        body: formData, // formData contains the appointment data
    })
    .then(response => response.json())
    .then(data => {
        // Handle the response from the server
        console.log(data);
        renderCalendar();
        displayPatientAppointments();
        // You can perform further actions based on the response, such as showing a success message to the user
    })
    .catch(error => {
        // Handle any errors that occur during the fetch request
        console.error('Error submitting appointment:', error);
    });
}

// Function to display a modal with the appointment form
function showModal(content) {
    var modal = document.createElement('div');
    modal.classList.add('modal');
    var modalContent = document.createElement('div');
    modalContent.classList.add('modal-content');
    modalContent.appendChild(content); // Append the form to the modal content
    var closeButton = document.createElement('button');
    closeButton.type = 'button';
    closeButton.textContent = 'Close';
    closeButton.addEventListener('click', function() {
        closeModal(); // Close the modal when the close button is clicked
    });
    modalContent.appendChild(closeButton); // Append the close button to the modal content
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
}

// Function to close the modal
function closeModal() {
    var modal = document.querySelector('.modal');
    if (modal) {
        document.body.removeChild(modal);
    }
}

        // Function to fetch appointments via AJAX
        function fetchAppointments(branchID, date) {
            $.ajax({
                url: './APIs/fetch-appointments.php',
                type: 'GET',
                data: { branch: branchID, date: date },
                dataType: 'json',
                success: function(data) {
                    renderAppointments(data);
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching appointments:', error);
                }
            });
        }

        // Function to render appointments on the calendar
        function renderAppointments(appointments) {
            // Clear existing appointment data before rendering new appointments
            var appointmentElements = document.querySelectorAll('.appointment');
            appointmentElements.forEach(function(element) {
                element.remove();
            });

            // Filter appointments to include only those within the current day
            var currentDayAppointments = appointments.filter(function(appointment) {
                var appointmentDate = dayjs(appointment.date);
                return appointmentDate.isSame(currentDate, 'day');
            });

            currentDayAppointments.forEach(function(appointment) {
                // Construct start time using date and time properties
                var startTime = dayjs(appointment.date + ' ' + appointment.time);
                // Calculate end time by adding 30 minutes to the start time
                var endTime = startTime.add(30, 'minute');
                // Format the start and end times
                var startTimeFormatted = startTime.format('HH:mm');
                var endTimeFormatted = endTime.format('HH:mm');
                // Use a default title if appointment title is undefined
                var appointmentTitle = appointment.title || 'Unavailable';
                // Calculate appointment duration
                var appointmentDuration = 30; // 30 minutes
                // Calculate the row index based on the hour component of the start time
                var startTimeHour = startTime.hour();
                var rowIndex = (startTimeHour >= 8 && startTimeHour < 18) ? (startTimeHour - 8) * 2 + (startTime.minute() >= 30 ? 1 : 0) : null;

                // Find the corresponding row in the table body
                var tableBody = document.querySelector('table tbody');
                var row = tableBody ? tableBody.rows[rowIndex] : null;
                // Find the corresponding cell in the row (default to column 1)
                var cell = row ? row.cells[1] : null;

                // Ensure that both row and cell are defined before appending the appointment element
                if (row && cell) {
                    // Create appointment element
                    var appointmentElement = document.createElement('div');
                    appointmentElement.textContent = appointmentTitle + ' (' + startTimeFormatted + ' - ' + endTimeFormatted + ')';
                    appointmentElement.classList.add('appointment');
                    appointmentElement.style.height = appointmentDuration * 2 + 'px';

                    // Append appointment element to the cell
                    cell.appendChild(appointmentElement);
                }
            });
        }

        // Listen for change event on the branchSelect dropdown
        document.getElementById('branchSelect').addEventListener('change', function() {
            selectedBranchID = this.value; // Update the selectedBranchID when a branch is selected

            if (selectedBranchID) {
                // Fetch appointments for the selected branch and current date
                fetchAppointments(selectedBranchID, currentDate.format('YYYY-MM-DD'));
            } else {
                console.error('No branch selected.');
                // Handle the case where no branch is selected
            }
        });

        // Event listener for toggle view button
        document.getElementById('toggleView').addEventListener('click', function() {
            selectedView = selectedView === 'day' ? 'month' : 'day';
            renderCalendar();
        });

        // Event listener for previous button
        document.getElementById('prev').addEventListener('click', function() {
            currentDate = selectedView === 'day' ? currentDate.subtract(1, 'day') : currentDate.subtract(1, 'month');
            renderCalendar();
        });

        // Event listener for next button
        document.getElementById('next').addEventListener('click', function() {
            currentDate = selectedView === 'day' ? currentDate.add(1, 'day') : currentDate.add(1, 'month');
            renderCalendar();
        });

        // Function to render the monthly view
        function renderMonthlyView(container) {
                   // Clear previous contentdai
        container.innerHTML = '';

        // Display current month and year
        document.getElementById('calendarTitle').textContent = currentDate.format('MMMM YYYY');

        // Create table element
        var table = document.createElement('table');
        table.classList.add('monthly-view');

        // Create table header for days of the week
        var thead = document.createElement('thead');
        var daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        var headerRow = document.createElement('tr');
        daysOfWeek.forEach(function(day) {
            var th = document.createElement('th');
            th.textContent = day;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        // Create table body for days of the month
        var tbody = document.createElement('tbody');
        var firstDayOfMonth = currentDate.startOf('month').day();
        var daysInMonth = currentDate.daysInMonth();
        var currentRow = document.createElement('tr');
        // Add empty cells for days before the start of the month
        for (var i = 0; i < firstDayOfMonth; i++) {
            var emptyCell = document.createElement('td');
            currentRow.appendChild(emptyCell);
        }
        // Add cells for each day of the month
        for (var day = 1; day <= daysInMonth; day++) {
            var cell = document.createElement('td');
            cell.textContent = day;
            cell.dataset.date = currentDate.format('YYYY-MM') + '-' + day;
            cell.addEventListener('click', function(event) {
                // Handle click on day cell to navigate to daily view
                var selectedDate = dayjs(event.target.dataset.date);
                currentDate = selectedDate;
                selectedView = 'day';
                renderCalendar();
            });
            currentRow.appendChild(cell);
            // Start new row for next week
            if ((firstDayOfMonth + day - 1) % 7 === 6 || day === daysInMonth) {
                tbody.appendChild(currentRow);
                currentRow = document.createElement('tr');
            }
        }
        table.appendChild(tbody);
        container.appendChild(table);
    }
    
function displayPatientAppointments() {
    // Call the fetch_patient_appointments.php API
    fetch('./APIs/fetch_patient_appointments.php', {
        method: 'GET',
    })
    .then(response => response.json())
    .then(data => {
        // Process the response and extract appointment data
        const appointments = data;

        // Select the table body to insert appointments
        const tableBody = document.querySelector('#appointmentsTable tbody');

        // Clear any existing content in the table body
        tableBody.innerHTML = '';

// Populate table rows with appointment data
appointments.forEach(appointment => {
    const { id, date, time, status, purpose } = appointment; // Destructure the appointment object

    // Calculate the time difference between now and the appointment time
    const appointmentDateTime = dayjs(date + ' ' + time);
    const timeDifference = appointmentDateTime.diff(dayjs(), 'hours');

    // Create a new row for the table
    const row = tableBody.insertRow();

    // Insert cells with data in the correct order
    const idCell = row.insertCell(); // Add a cell for the appointment ID
    idCell.textContent = id; // Display the appointment ID

    const dateCell = row.insertCell();
    dateCell.textContent = date;

    const timeCell = row.insertCell();
    timeCell.textContent = time;

    const statusCell = row.insertCell();
    statusCell.textContent = status;

    // Create buttons for editing and canceling appointments
    const editButton = document.createElement('button');
    editButton.textContent = 'Edit';

    // Add an event listener to the edit button
    editButton.addEventListener('click', (function(appointmentId) {
        return function() {
            // Pass the appointment ID to the edit modal function
            openEditModal(appointmentId);
        };
    })(id));

    const cancelButton = document.createElement('button');
    cancelButton.textContent = 'Cancel';
    cancelButton.addEventListener('click', function() {

    });

    // Check if the appointment is less than 24 hours away
    if (timeDifference < 24) {
        // Disable editing and canceling for appointments less than 24 hours away
        editButton.disabled = true;
        cancelButton.disabled = true;
    }

    // Append buttons to a cell in the row
    const actionCell = row.insertCell();
    actionCell.appendChild(editButton);
    actionCell.appendChild(cancelButton);
});


    })
    .catch(error => {
        console.error('Error fetching appointments:', error);
    });
}

// Call the function to display the patient's appointments
displayPatientAppointments();

function openEditModal(appointmentId) {
    // Log the appointment ID to the console for debugging
    console.log('Appointment ID:', appointmentId);

    // Construct the URL with the appointment ID as a query string parameter
    const url = `./APIs/fetch-appointment-details.php?id=${appointmentId}`;

    // Fetch appointment details for the given appointmentId
    fetch(url, {
        method: 'GET',
    })
    .then(response => response.json())
    .then(appointment => {
        // Populate the edit modal with appointment details
        document.getElementById('editDate').value = appointment.date;
        document.getElementById('editTime').value = appointment.time;
        // Open the edit modal
        showModal('editAppointmentModal');
    })
    .catch(error => {
        console.error('Error fetching appointment details:', error);
    });
}

});
</script>
</body>
</html>