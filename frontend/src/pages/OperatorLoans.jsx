import Modal from '../components/Modal.jsx';
import { formatDate } from '../formatDate.js';
import { useState } from 'react';
import { api } from '../api.js';
import useData from '../hooks/useData.js';
import Button from '../components/Button.jsx';
import Toast from '../components/Toast.jsx';
import Status from '../components/Status.jsx';

const filters = [
  ['', 'Të gjitha'],
  ['active', 'Aktive'],
  ['overdue', 'Të vonuara'],
  ['awaiting_payment', 'Pret pagesën'],
  ['return_requested', 'Kërkesa kthimi'],
];
export default function OperatorLoans({ onChange, refreshKey }) {
  const [filter, setFilter] = useState('');
  const [query, setQuery] = useState('');
  const [student, setStudent] = useState(null);
  const [busy, setBusy] = useState(null);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const {
    data,
    error: loadError,
    loading,
  } = useData(
    `/operator/loans${filter ? `?status=${filter}` : ''}`,
    refreshKey,
  );
  const loans = (data?.loans || []).filter((l) =>
    `${l.user.name} ${l.user.email} ${l.book.title}`
      .toLowerCase()
      .includes(query.toLowerCase()),
  );
  async function run(loan, pay) {
    const label = pay
      ? `Regjistro pagesën prej €${loan.penalty?.amount} për ${loan.user.name}?`
      : `Konfirmo kthimin fizik të “${loan.book.title}”?`;
    if (!window.confirm(label)) return;
    setBusy(loan.id);
    setError('');
    try {
      await api(`/loans/${loan.id}/${pay ? 'penalty/pay' : 'return'}`, {
        method: 'POST',
      });
      setMessage(
        pay
          ? 'Pagesa u regjistrua dhe huazimi u mbyll.'
          : 'Kthimi u regjistrua. Stoku u përditësua.',
      );
      onChange();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(null);
    }
  }
  return (
    <section className="card">
      <div className="section-heading">
        <div>
          <span className="eyebrow">QARKULLIMI I LIBRAVE</span>
          <h2>Huazimet dhe kthimet</h2>
        </div>
        <Button className="btn-secondary" onClick={onChange}>
          Rifresko
        </Button>
      </div>
      <div className="toolbar">
        <div className="segmented">
          {filters.map(([id, label]) => (
            <button
              key={id}
              className={filter === id ? 'active' : ''}
              onClick={() => setFilter(id)}
            >
              {label}
            </button>
          ))}
        </div>
        <label className="search-field">
          <span className="sr-only">Kërko student ose libër</span>
          <input
            className="input"
            placeholder="Kërko student, email ose libër…"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
        </label>
      </div>
      {(loadError || error) && (
        <p className="error-box" role="alert">
          {loadError || error}
        </p>
      )}
      {loading ? (
        <p className="empty">Duke ngarkuar…</p>
      ) : (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Studenti</th>
                <th>Libri</th>
                <th>Afati</th>
                <th>Statusi</th>
                <th>Kërkesa për kthim</th>
                <th>Penaliteti</th>
                <th>Veprimet</th>
              </tr>
            </thead>
            <tbody>
              {loans.map((l) => (
                <tr key={l.id}>
                  <td>
                    <button
                      className="text-button"
                      onClick={() => setStudent(l.user)}
                    >
                      {l.user.name}
                    </button>
                    <small className="block muted">{l.user.email}</small>
                  </td>
                  <td>{l.book.title}</td>
                  <td>{formatDate(l.due_at)}</td>
                  <td>
                    <Status value={l.status} />
                  </td>
                  <td>
                    {l.return_requested_at
                      ? formatDate(l.return_requested_at)
                      : '—'}
                  </td>
                  <td>
                    {l.penalty
                      ? `€${l.penalty.amount} · ${l.penalty.days_late} ditë · ${l.penalty.paid ? 'paguar' : 'papaguar'}`
                      : '—'}
                  </td>
                  <td>
                    {!l.returned_at && (
                      <Button
                        disabled={busy !== null}
                        loading={busy === l.id}
                        onClick={() => run(l, false)}
                      >
                        Regjistro kthimin
                      </Button>
                    )}
                    {l.status === 'awaiting_payment' && (
                      <Button
                        disabled={busy !== null}
                        loading={busy === l.id}
                        onClick={() => run(l, true)}
                      >
                        Paguaj €{l.penalty?.amount}
                      </Button>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {!loans.length && (
            <p className="empty">Nuk ka huazime për këta filtra.</p>
          )}
        </div>
      )}
      {student && (
        <Modal
          label={`Historiku i ${student.name}`}
          onClose={() => setStudent(null)}
        >
          <div className="section-heading">
            <h2>{student.name}</h2>
            <Button
              autoFocus
              className="btn-secondary"
              onClick={() => setStudent(null)}
            >
              Mbyll
            </Button>
          </div>
          <StudentHistory student={student} refreshKey={refreshKey} />
        </Modal>
      )}
      <Toast message={message} onClose={() => setMessage('')} />
    </section>
  );
}

function StudentHistory({ student, refreshKey }) {
  const history = useData(`/operator/users/${student.id}/loans`, refreshKey);
  const penalties = useData(
    `/operator/users/${student.id}/penalties`,
    refreshKey,
  );
  if (history.error || penalties.error)
    return <p className="error-box">{history.error || penalties.error}</p>;
  if (history.loading || penalties.loading)
    return <p>Duke ngarkuar historikun…</p>;
  return (
    <>
      <p className="muted">{student.email}</p>
      <h3>Historiku i plotë</h3>
      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Libri</th>
              <th>Afati</th>
              <th>Statusi</th>
            </tr>
          </thead>
          <tbody>
            {history.data.loans.map((l) => (
              <tr key={l.id}>
                <td>{l.book.title}</td>
                <td>{formatDate(l.due_at)}</td>
                <td>
                  <Status value={l.status} />
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <h3>Penalitetet</h3>
      {penalties.data.penalties.map((p) => (
        <p key={p.id}>
          Huazimi #{p.loan_id} · €{p.amount} · {p.days_late} ditë ·{' '}
          {p.paid ? 'Paguar' : 'Papaguar'}
        </p>
      ))}
      {!penalties.data.penalties.length && (
        <p className="muted">Asnjë penalitet.</p>
      )}
    </>
  );
}
