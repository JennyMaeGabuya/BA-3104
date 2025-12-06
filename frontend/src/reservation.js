// WARNING SPAGHETTI CODE BELOW 

import './reservation.css'
import { sendReservation } from './utils/api';

const zone = document.getElementById("zone")
const spotList = document.getElementById("spotList");
const modal = document.getElementById("modal");
const modalContent = document.getElementById("modalContent");
const modalCancel = document.getElementById("modalCancel");
const modalConfirm = document.getElementById("modalConfirm");
const previewBtn = document.getElementById("previewBtn");

let selectedSpot = null;


function generateZones(zone) {

  fetch("http://localhost/ParkEase/BA-3104/backend/public/save-reservation") // get all the reservations
  .then(res => res.text())
  .then(xmlString => {
    const parser = new DOMParser();
    const xml = parser.parseFromString(xmlString, "text/xml");
    console.log(xml);
  })
  .catch(err => console.error(err));

  // in the future GET ZONES FROM DATABASE BACKEND
  const spots = [];
  for(let i = 1; i <= 12; i++) {
    const mySpot = {id : `${zone}-${i}` , occupied: Math.random() < 0.18}
    spots.push(mySpot);
  }
  return spots;
}


function getFormData() {
  return {
    fullName: document.getElementById("fullName").value,
    studentId: document.getElementById("studentId").value,
    vPlate: document.getElementById("vPlate").value,
    vType: document.getElementById("vType").value,
    rDate: document.getElementById("rDate").value,
    startTime: document.getElementById("startTime").value,
    endTime: document.getElementById("endTime").value,
    spot: selectedSpot
  }
}

function updatePreviewButton() {
  previewBtn.disabled = !selectedSpot;
  if (selectedSpot) {
    previewBtn.classList.remove("opacity-50");
    previewBtn.classList.add("cursor-pointer");
  } else {
    previewBtn.classList.add("opacity-50");
    previewBtn.classList.remove("cursor-pointer");
  }
}

function validateForm() {
  const fullName = document.getElementById("fullName").value.trim();
  const studentId = document.getElementById("studentId").value.trim();
  const vPlate = document.getElementById("vPlate").value.trim();
  const rDate = document.getElementById("rDate").value;
  const startTime = document.getElementById("startTime").value;
  const endTime = document.getElementById("endTime").value;

  if (!fullName) {
    alert("Please enter your full name.");
    return false;
  }

  if (!studentId) {
    alert("Please enter your student or staff ID.");
    return false;
  }

  if (!vPlate) {
    alert("Please enter your vehicle plate.");
    return false;
  }

  if (!rDate) {
    alert("Please select a reservation date.");
    return false;
  }

  if (!startTime || !endTime) {
    alert("Please select both start and end times.");
    return false;
  }

  if (startTime >= endTime) {
    alert("End time must be after start time.");
    return false;
  }

  if (!selectedSpot) {
    alert("Please select a parking spot.");
    return false;
  }

  return true;
}


function generateSpots() {
  selectedSpot = null; // reset selection on zone change
  updatePreviewButton();

  const zoneAl = zone.value;
  const zones = generateZones(zoneAl);

  spotList.innerHTML = "";
  zones.forEach(z => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = z.id + (z.occupied ? " (occupied)" : "");
    btn.className = `
      spot px-3 py-2 rounded-md border transition-all
      ${z.occupied ? "bg-red-200 text-gray-400 cursor-not-allowed" : "bg-white hover:bg-amber-100 border-amber-300"}
    `;
    btn.disabled = z.occupied;

    btn.addEventListener("click", () => {
      document.querySelectorAll(".spot").forEach(b => b.classList.remove("bg-amber-300"));
      btn.classList.add("bg-amber-300");
      selectedSpot = z.id;
      updatePreviewButton();
    });

    spotList.appendChild(btn);
  });
}

zone.addEventListener("change", generateSpots);


previewBtn.addEventListener("click", () => {
  if(!validateForm()) return;
  const data = getFormData();

  modalContent.innerHTML = `
    <p><strong>Full Name:</strong> ${data.fullName}</p>
    <p><strong>Student/Staff ID:</strong> ${data.studentId}</p>
    <p><strong>Vehicle Plate:</strong> ${data.vPlate}</p>
    <p><strong>Vehicle Type:</strong> ${data.vType}</p>
    <p><strong>Date:</strong> ${data.rDate}</p>
    <p><strong>Time:</strong> ${data.startTime} - ${data.endTime}</p>
    <p><strong>Spot:</strong> ${data.spot}</p>
  `;

  modal.classList.remove("hidden");
  modal.classList.add("flex");
});

modalCancel.addEventListener("click", () => {
  modal.classList.add("hidden");
  modal.classList.remove("flex");
});

// CONFIRM - SEND SOME DATA - POST
modalConfirm.addEventListener("click", () => {
  const data = getFormData();
  const xmlData = generateXML(data);
  sendReservation(xmlData, data);
});


// GENERATE THE XML TO STORE IN DATABASE
function generateXML(data) {
  const xmlDoc = document.implementation.createDocument("", "", null);
  const reservation = xmlDoc.createElement("reservation");
  // creates <reservation></reservation>

  for (const key in data) {
    const element = xmlDoc.createElement(key); // example creates <fullName> </fullname>  
    element.textContent = data[key]; // then sets the text content to data at key
    reservation.appendChild(element);// append it
  }

  xmlDoc.appendChild(reservation);

  // Convert to string
  const serializer = new XMLSerializer();
  return serializer.serializeToString(xmlDoc);
}

generateSpots();