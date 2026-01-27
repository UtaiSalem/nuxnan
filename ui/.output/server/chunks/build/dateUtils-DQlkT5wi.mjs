const formatDateForInput = (dateString) => {
  if (!dateString) return "";
  try {
    const dateParts = dateString.split("T")[0];
    return dateParts;
  } catch (error) {
    console.warn("Date formatting error:", error);
    return "";
  }
};
const formatDateThai = (dateString, options = {}) => {
  if (!dateString) return options.defaultText || "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38";
  try {
    const dateParts = dateString.split("T")[0].split("-");
    const year = parseInt(dateParts[0]);
    const month = parseInt(dateParts[1]) - 1;
    const day = parseInt(dateParts[2]);
    const thaiMonths = [
      "\u0E21\u0E01\u0E23\u0E32\u0E04\u0E21",
      "\u0E01\u0E38\u0E21\u0E20\u0E32\u0E1E\u0E31\u0E19\u0E18\u0E4C",
      "\u0E21\u0E35\u0E19\u0E32\u0E04\u0E21",
      "\u0E40\u0E21\u0E29\u0E32\u0E22\u0E19",
      "\u0E1E\u0E24\u0E29\u0E20\u0E32\u0E04\u0E21",
      "\u0E21\u0E34\u0E16\u0E38\u0E19\u0E32\u0E22\u0E19",
      "\u0E01\u0E23\u0E01\u0E0E\u0E32\u0E04\u0E21",
      "\u0E2A\u0E34\u0E07\u0E2B\u0E32\u0E04\u0E21",
      "\u0E01\u0E31\u0E19\u0E22\u0E32\u0E22\u0E19",
      "\u0E15\u0E38\u0E25\u0E32\u0E04\u0E21",
      "\u0E1E\u0E24\u0E28\u0E08\u0E34\u0E01\u0E32\u0E22\u0E19",
      "\u0E18\u0E31\u0E19\u0E27\u0E32\u0E04\u0E21"
    ];
    const thaiMonthsShort = [
      "\u0E21.\u0E04.",
      "\u0E01.\u0E1E.",
      "\u0E21\u0E35.\u0E04.",
      "\u0E40\u0E21.\u0E22.",
      "\u0E1E.\u0E04.",
      "\u0E21\u0E34.\u0E22.",
      "\u0E01.\u0E04.",
      "\u0E2A.\u0E04.",
      "\u0E01.\u0E22.",
      "\u0E15.\u0E04.",
      "\u0E1E.\u0E22.",
      "\u0E18.\u0E04."
    ];
    const monthNames = options.shortMonth ? thaiMonthsShort : thaiMonths;
    const thaiMonth = monthNames[month];
    const buddhistYear = year + 543;
    if (options.format === "full") {
      const thaiDays = ["\u0E2D\u0E32\u0E17\u0E34\u0E15\u0E22\u0E4C", "\u0E08\u0E31\u0E19\u0E17\u0E23\u0E4C", "\u0E2D\u0E31\u0E07\u0E04\u0E32\u0E23", "\u0E1E\u0E38\u0E18", "\u0E1E\u0E24\u0E2B\u0E31\u0E2A\u0E1A\u0E14\u0E35", "\u0E28\u0E38\u0E01\u0E23\u0E4C", "\u0E40\u0E2A\u0E32\u0E23\u0E4C"];
      const date = new Date(year, month, day);
      const dayName = thaiDays[date.getDay()];
      return `\u0E27\u0E31\u0E19${dayName}\u0E17\u0E35\u0E48 ${day} ${thaiMonth} \u0E1E.\u0E28. ${buddhistYear}`;
    }
    if (options.format === "short") {
      return `${day}/${month + 1}/${buddhistYear}`;
    }
    return `${day} ${thaiMonth} ${buddhistYear}`;
  } catch (error) {
    console.warn("Date formatting error:", error);
    return dateString;
  }
};
const calculateAge = (dateString) => {
  if (!dateString) return "-";
  try {
    const dateParts = dateString.split("T")[0].split("-");
    const birthYear = parseInt(dateParts[0]);
    const birthMonth = parseInt(dateParts[1]) - 1;
    const birthDay = parseInt(dateParts[2]);
    const today = /* @__PURE__ */ new Date();
    const currentYear = today.getFullYear();
    const currentMonth = today.getMonth();
    const currentDay = today.getDate();
    let age = currentYear - birthYear;
    if (currentMonth < birthMonth || currentMonth === birthMonth && currentDay < birthDay) {
      age--;
    }
    return age;
  } catch (error) {
    console.warn("Age calculation error:", error);
    return "-";
  }
};
const isValidDate = (dateString) => {
  if (!dateString) return false;
  try {
    const dateParts = dateString.split("T")[0].split("-");
    if (dateParts.length !== 3) return false;
    const year = parseInt(dateParts[0]);
    const month = parseInt(dateParts[1]);
    const day = parseInt(dateParts[2]);
    if (year < 1900 || year > 2100) return false;
    if (month < 1 || month > 12) return false;
    if (day < 1 || day > 31) return false;
    const date = new Date(year, month - 1, day);
    return date.getMonth() === month - 1 && date.getDate() === day;
  } catch (error) {
    return false;
  }
};
const formatTimeAgo = (dateString) => {
  if (!dateString) return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E27\u0E25\u0E32";
  try {
    const date = new Date(dateString);
    const now = /* @__PURE__ */ new Date();
    const diffInSeconds = Math.floor((now - date) / 1e3);
    if (diffInSeconds < 60) return "\u0E40\u0E21\u0E37\u0E48\u0E2D\u0E2A\u0E31\u0E01\u0E04\u0E23\u0E39\u0E48";
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} \u0E19\u0E32\u0E17\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} \u0E0A\u0E31\u0E48\u0E27\u0E42\u0E21\u0E07\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} \u0E27\u0E31\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    if (diffInSeconds < 2592e3) return `${Math.floor(diffInSeconds / 604800)} \u0E2A\u0E31\u0E1B\u0E14\u0E32\u0E2B\u0E4C\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    if (diffInSeconds < 31536e3) return `${Math.floor(diffInSeconds / 2592e3)} \u0E40\u0E14\u0E37\u0E2D\u0E19\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
    return `${Math.floor(diffInSeconds / 31536e3)} \u0E1B\u0E35\u0E17\u0E35\u0E48\u0E41\u0E25\u0E49\u0E27`;
  } catch (error) {
    console.warn("Time ago formatting error:", error);
    return "\u0E44\u0E21\u0E48\u0E23\u0E30\u0E1A\u0E38\u0E40\u0E27\u0E25\u0E32";
  }
};
const formatDate = (dateString, format = "thai") => {
  if (!dateString) return "";
  switch (format) {
    case "thai":
      return formatDateThai(dateString);
    case "short":
      return formatDateThai(dateString, { format: "short" });
    case "full":
      return formatDateThai(dateString, { format: "full" });
    case "iso":
      return formatDateForInput(dateString);
    default:
      return formatDateThai(dateString);
  }
};
const getCurrentDate = () => {
  const today = /* @__PURE__ */ new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, "0");
  const day = String(today.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
};
const getCurrentDateThai = () => {
  return formatDateThai(getCurrentDate());
};

export { getCurrentDate as a, formatDate as b, formatDateThai as c, formatDateForInput as d, calculateAge as e, formatTimeAgo as f, getCurrentDateThai as g, isValidDate as i };
//# sourceMappingURL=dateUtils-DQlkT5wi.mjs.map
