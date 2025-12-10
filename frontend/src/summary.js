
// SUMMMARY
const downloadBtn = document.getElementById("downloadBtn");
const summaryBody = document.getElementById("summaryBody");

// Function to create receipt text
function generateReceiptText(data) {
  return `
PARKING RESERVATION RECEIPT
===========================

Full Name: ${data.fullName}
Student/Staff ID: ${data.studentId}
Vehicle Plate: ${data.vPlate}
Vehicle Type: ${data.vType}
Date: ${data.rDate}
Time: ${data.startTime} - ${data.endTime}
Spot: ${data.spot}

Thank you for using ParkEase!
===========================
`;
}

// Call this whenever you update the summary
export function updateSummary(data) {
  summaryBody.innerHTML = `
    <p><strong>Full Name:</strong> ${data.fullName}</p>
    <p><strong>Student/Staff ID:</strong> ${data.studentId}</p>
    <p><strong>Vehicle Plate:</strong> ${data.vPlate}</p>
    <p><strong>Vehicle Type:</strong> ${data.vType}</p>
    <p><strong>Date:</strong> ${data.rDate}</p>
    <p><strong>Time:</strong> ${data.startTime} - ${data.endTime}</p>
    <p><strong>Spot:</strong> ${data.spot}</p>
  `;

  // Enable download button
  downloadBtn.disabled = false;
  downloadBtn.classList.remove("opacity-50", "cursor-not-allowed");
}
if(downloadBtn) {
    downloadBtn.addEventListener("click", () => {
    const data = getFormData();
    const doc = new jsPDF();

    doc.setFontSize(16);
    doc.text("PARKING RESERVATION RECEIPT", 20, 20);
    doc.setFontSize(12);
    doc.text(`Full Name: ${data.fullName}`, 20, 40);
    doc.text(`Student/Staff ID: ${data.studentId}`, 20, 50);
    doc.text(`Vehicle Plate: ${data.vPlate}`, 20, 60);
    doc.text(`Vehicle Type: ${data.vType}`, 20, 70);
    doc.text(`Date: ${data.rDate}`, 20, 80);
    doc.text(`Time: ${data.startTime} - ${data.endTime}`, 20, 90);
    doc.text(`Spot: ${data.spot}`, 20, 100);

    doc.save(`ParkEase_Receipt_${data.rDate}_${data.spot}.pdf`);
  });
}
