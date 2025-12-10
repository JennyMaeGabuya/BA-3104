import '../src/style.css';

const table = document.getElementById("reservationTable");
const refreshBtn = document.getElementById("refreshBtn");
const deleteAllBtn = document.getElementById("deleteAllBtn");
const logoutAdmin = document.getElementById("logoutAdmin");


console.log(table)
console.log(refreshBtn)
console.log(deleteAllBtn)
console.log(logoutAdmin)

getReservations();

function to12Hour(time24) {
  const [hourStr, minute, second] = time24.split(":");
  let hour = parseInt(hourStr);
  const ampm = hour >= 12 ? "PM" : "AM";
  hour = hour % 12 || 12;  // convert 0 → 12
  return `${hour}:${minute} ${ampm}`;
}

async function getReservations() {
  table.innerHTML = `<tr><td colspan="7" class="text-center py-4">loading... </td></tr>`;

    const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/get-all-reservations", {
      method : "GET",
      credentials : "include"
    })
    const data = await res.json();
    console.log("ADMIN RESERVATIONS:", data);
    
    if(!data.reservations || data.reservations.length === 0) {
      table.innerHTML = `<tr><td colspan="7" class="text-center py-4">No reservations found.</td></tr>`;
      return;
    }

    table.innerHTML = "";
    data.reservations.forEach(r => {
    const row = document.createElement("tr");
    row.classList.add("border-b");

    row.innerHTML = `
      <td class="py-2">${r.fullName}</td>
      <td class="py-2">${r.studentId}</td>
      <td class="py-2">${r.vPlate}</td>
      <td class="py-2">${r.rDate}</td>
      <td class="py-2">${to12Hour(r.startTime)} - ${to12Hour(r.endTime)}</td>
      <td class="py-2">${r.spot}</td>
      <td class="py-2">
        <button data-id="${r.studentId}" class="deleteBtn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-700">
          Delete
        </button>
      </td>
    `;

    table.appendChild(row);
  });

  document.querySelectorAll(".deleteBtn").forEach(btn => {
    btn.addEventListener("click", () => {
      // deleteReservation(btn.dataset.id);
      console.log("delete reserv")
    });
  });
  
}