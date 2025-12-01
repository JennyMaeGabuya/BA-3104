import './reservation.css'


const zone = document.getElementById("zone")
const spotList = document.getElementById("spotList");

function generateZones(zone) {
  const spots = [];
  for(let i = 0; i < 12; i++) {
    const mySpot = {id : `${zone} - ${i}` , occupied  : Math.random() < 0.18}
    spots.push(mySpot);
  }
  return spots;
}


function getFormData() {
  const fullName = document.getElementById("fullName").value;
  const studentId = document.getElementById("studentId").value;
  const vPlate = document.getElementById("vPlate").value;
  const vType = document.getElementById("vType").value;
  const rDate = document.getElementById("rDate").value;
  const startTime = document.getElementById("startTime").value;
  const endTime = document.getElementById("endTime").value;
  const data = {
    fullName : fullName,
    studentId : studentId,
    vPlate : vPlate,
    vType : vType,
    rDate : rDate,
    startTime : startTime,
    endTime : endTime,
  }
  return data;
}

function generateSpots() {
  const zoneAl = zone.value;
  const spotList = document.getElementById("spotList");
  const zones = generateZones(zoneAl);

  let zonesHtml = undefined;
  spotList.innerHTML = ""; // clear old buttons first
  zones.forEach((z) => {
    spotList.innerHTML += `
      <button class="spot border-amber-200 px-2 py-1 rounded-md ${z.occupied ? 'bg-gray-200 text-gray-400' : 'bg-white hover:bg-amber-100'}" ${z.occupied ? "disabled" : ""}>
        ${z.id} ${z.occupied ? "(occupied)" : ""}
      </button>
    `;
    
  });
}

generateSpots();
// generate when zone change
zone.addEventListener("change" , generateSpots)

document.getElementById("previewBtn").addEventListener("click", () => {
  const data = getFormData();
  console.log(data)
})