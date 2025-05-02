const form = document.getElementById('form')
const queryInput = form.querySelector("input[name='query'")
const interestSelect = form.querySelector("select[name='interest']")

form.addEventListener('submit', (e) => {
  e.preventDefault()
  search()
})

const debounce = (fn, delay) => {
  let timeoutId;
  return (...args) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => fn(...args), delay);
  };
};

const search = async () => {
  const url = new URL(window.location.href);

  const data = [...new FormData(form)].filter(([, value]) => !!value);
  url.pathname = data.map(([key, value]) => `${key}:${value}`).join('/')
  
  const result = await fetch(url.toString()).then(response => response.text())

  const dom = new DOMParser().parseFromString(result, 'text/html');
  const newResults = dom.getElementById('search-results')
  
  const oldResults = document.getElementById('search-results')
  oldResults.replaceWith(newResults)

  url.searchParams.delete('page');
  window.history.replaceState({}, '', url.toString());
}

queryInput.addEventListener('input', debounce(search, 300))
interestSelect.addEventListener('change', search)