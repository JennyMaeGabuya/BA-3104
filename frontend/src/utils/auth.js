import { showModal } from "../utils/utils.js";

function openErrorModal(message) {
  const modalMessage = document.getElementById("modalMessage");
  modalMessage.textContent = message;

  // Check the checkbox to open the modal
  const modalToggle = document.getElementById("errorModalToggle");
  setTimeout(() => {
    modalToggle.checked = true;
  }, 100)
  modalToggle.nextElementSibling.classList.remove("hidden");

}

document.addEventListener("submit", async (e) => {
  if (e.target.id !== "loginForm") return;
  e.preventDefault();

  const formData = new FormData(e.target);

  const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/login", {
    method: "POST",
    body: formData,
    credentials : "include"
  });

  const data = await res.json();
  console.log(data)
  if (data.status?.trim() === "success") {
    window.location.href = "reservation.html";
  } else if (data.status?.trim() === "error") {
    console.log("MODAL SHOULD OPEN");
    openErrorModal(data.message)
  }
});


if(document.getElementById("goReservation")) {
    // UNAUTHORIZED MODAL
  document.getElementById("goReservation").addEventListener("click", async (e) => {
    e.preventDefault(); // block navigation for now
    
    const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/check-auth", {
      method: "GET",
      credentials: "include"
    });
    const modalToggle = document.getElementById("authBlockModal");
    console.log(modalToggle)
    if (!res.ok) {
      // User is NOT logged in → show modal
      modalToggle.nextElementSibling.classList.remove("hidden");
      setTimeout(() => {
        modalToggle.checked = true;
      }, 100)
      return;
    }

    // User is logged in → allow navigation
    window.location.href = "reservation.html";
  });
}




document.addEventListener("submit" , async (e) => {
  if (e.target.id !== "signupForm") return;
  e.preventDefault();

  const formData = new FormData(e.target);

  const res = await fetch("http://localhost/ParkEase/BA-3104/backend/public/signup",{
    method : "POST",
    body : formData
  });
  const data = await res.json();
  console.log(data);
  if (data.status === "success") {
    window.location.href = "verify.html";
  } else {
    alert(data.message || "Error");
  }

});