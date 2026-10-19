function normalizeSearchTerm(term) {
  return String(term == null ? "" : term).trim().toLowerCase();
}

function searchWords(term) {
  const normalizedTerm = normalizeSearchTerm(term);
  return normalizedTerm ? normalizedTerm.split(/\s+/) : [];
}

function buildSearchText(values) {
  const searchValues = Array.isArray(values) ? values : [values];
  return searchValues
    .filter((value) => value !== null && value !== undefined)
    .map((value) => String(value))
    .join(" ")
    .toLowerCase();
}

function filterRowsBySearch(rows, term, getSearchValues) {
  const words = searchWords(term);
  if (words.length === 0) {
    return rows;
  }

  return rows.filter((row) => {
    const searchText = buildSearchText(getSearchValues(row));
    return words.every((word) => searchText.includes(word));
  });
}

function bindLiveSearch(selector, onSearch, delay = 250) {
  let searchTimer = null;
  const inputs = $(selector);

  inputs.off(".tableSearch");
  inputs.on("input.tableSearch", function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      searchTimer = null;
      onSearch();
    }, delay);
  });
  inputs.on("keydown.tableSearch", function (event) {
    if (event.key === "Enter") {
      event.preventDefault();
      clearTimeout(searchTimer);
      searchTimer = null;
      onSearch();
    }
  });
}
