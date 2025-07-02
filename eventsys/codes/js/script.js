window.onload = () => {
    document.querySelector('.form-container').classList.add('show');
};

let eventIdToDelete = null;
//  Filter search functionality for attendance table
document.getElementById("searchInput").addEventListener("keyup", function () {
    const input = this.value.toLowerCase();
    const rows = document.querySelectorAll("#attendanceTable tbody tr");

    rows.forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        row.style.display = (name.includes(input) || email.includes(input)) ? "" : "none";
    });
});

// Export attendance to excel or pdf
function exportToExcel() {
    let table = document.getElementById("attendanceTable");
    let rows = table.querySelectorAll("tbody tr");
    let csv = "Name,Email,Check-In,Check-Out,Status\\n";

    rows.forEach(row => {
        if (row.style.display !== "none") {
            let cols = row.querySelectorAll("td");
            let rowText = Array.from(cols).map(td => `"${td.innerText.trim()}"`).join(",");
            csv += rowText + "\\n";
        }
    });

    let blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "attendance_export.xls";
    link.click();
}

async function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    let table = document.getElementById("attendanceTable");
    let rows = table.querySelectorAll("tbody tr");

    doc.setFontSize(12);
    doc.text("Attendance Report", 14, 15);

    let startY = 25;
    let headers = ["Name", "Email", "Check-In", "Check-Out", "Status"];
    doc.autoTable({
        head: [headers],
        body: Array.from(rows).filter(r => r.style.display !== "none").map(row => {
            return Array.from(row.querySelectorAll("td")).map(td => td.innerText.trim());
        }),
        startY: startY,
        styles: { fontSize: 10 },
    });

    doc.save("attendance_export.pdf");
}

// Theme toggle functionality
function toggleTheme() {
    const body = document.body;
    const newMode = body.classList.contains("dark-mode") ? "light-mode" : "dark-mode";
    body.classList.remove("light-mode", "dark-mode");
    body.classList.add(newMode);
    localStorage.setItem("theme", newMode);
}

window.onload = () => {
    const savedTheme = localStorage.getItem("theme") || "light-mode";
    document.body.classList.add(savedTheme);
};

function showEventDetails(event_id) {
    const detailsContainer = document.getElementById("overlay_container");
    const eventTitle = document.querySelector("#event_title");
    const eventDes = document.querySelector("#event_description");
    const eventStart = document.querySelector("#event_start");
    const eventEnd = document.querySelector("#event_end");

    fetch('get-description.php?event_id=' + event_id)
    .then((response) => response.json()) 
    .then((data) => {
        eventTitle.innerHTML = data.title;
        eventDes.innerHTML = data.description;
        eventStart.innerHTML = "<strong>Start:</strong> " + data.start_time;
        eventEnd.innerHTML = "<strong>End:</strong> " + data.end_time;
    });

    detailsContainer.classList.toggle('hidden');
}

function deleteEvent(event_id) {
    fetch("delete-event.php?delete=" + event_id, {
    method: "DELETE",
    headers: {
        "Content-type": "application/x-www-form-urlencoded",
    },
  })
  .then((response) => response.text())
  .then((responseText) => {
    // alert(responseText);
  });
}

function showDeletePrompt(event_id) {
    eventIdToDelete = event_id;
    document.getElementById("overlay_container").classList.toggle('hidden');
    const confirmBtn = document.getElementById("confirmDeleteBtn");

    if (confirmBtn) {
        confirmBtn.onclick = function() {
            if (eventIdToDelete) {
                deleteEvent(eventIdToDelete);
                window.location.reload();
            }
        };
    }
}
