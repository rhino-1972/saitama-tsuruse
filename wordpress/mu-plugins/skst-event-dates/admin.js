(() => {
  const table = document.querySelector('#skst-event-dates-table tbody');
  const add = document.getElementById('skst-add-event-date');
  const template = document.getElementById('skst-event-date-template');
  if (!table || !add || !template) return;
  let index = table.children.length;
  add.addEventListener('click', () => {
    if (table.children.length >= 100) return;
    const row = template.content.cloneNode(true);
    row.querySelectorAll('input').forEach(input => {
      input.name = input.name.replace('__INDEX__', index);
    });
    index += 1;
    table.append(row);
    table.lastElementChild.querySelector('input').focus();
  });
  table.addEventListener('click', event => {
    const remove = event.target.closest('.skst-remove-event-date');
    if (remove) remove.closest('tr').remove();
  });
})();
