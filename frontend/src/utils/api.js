import { showModal } from "./utils";


export function sendReservation (xmlData, data) {
  console.log(data)
  fetch("http://localhost/ParkEase/BA-3104/backend/public/save-reservation", {
    method : "POST",
    headers : { "Content-type" : "application/xml" },
    body : xmlData
  }).then(response => response.text())
  .then(result => {
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(result, "application/xml");
    const successTag = xmlDoc.querySelector("success");
    
    if (successTag) {
        const message = successTag.textContent; // "Reservation saved"
        console.log(message);
        showModal(message); // call modal function
    } else {
        showModal("Something went wrong."); // fallback
    }                 // Log server response // <success></success>
    alert(`Reservation confirmed for spot ${data.spot}!`);
    modal.classList.add("hidden");       // Hide modal
    modal.classList.remove("flex");      // Remove flex display class
    setTimeout(() => {
      window.location.reload();
    },1000)
  })
  .catch(error => {
    console.error("Error saving reservation:", error);
    alert("Failed to save reservation. Please try again.");
  });
}