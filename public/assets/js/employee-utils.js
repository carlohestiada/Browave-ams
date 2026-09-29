function isNAEmployeeCode(code) {
  return typeof code === "string" && code.startsWith("N/A-");
}

function displayEmployeeCode(code) {
  if (isNAEmployeeCode(code)) return "N/A";
  return code === null || code === undefined || code === "" ? "-" : code;
}