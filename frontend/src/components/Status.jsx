const labels = {
  active: 'Aktiv',
  overdue: 'I vonuar',
  awaiting_payment: 'Pret pagesën',
  closed: 'Përfunduar',
};
export default function Status({ value }) {
  return (
    <span
      className={`badge badge-${value === 'overdue' ? 'bad' : value === 'awaiting_payment' ? 'warn' : 'ok'}`}
    >
      {labels[value] || value}
    </span>
  );
}
