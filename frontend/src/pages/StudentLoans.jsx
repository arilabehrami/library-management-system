import { formatDate } from '../formatDate.js';
import { useState } from 'react';
import useData from '../hooks/useData.js';
import Status from '../components/Status.jsx';
import Button from '../components/Button.jsx';
import Toast from '../components/Toast.jsx';
import { api } from '../api.js';

export default function StudentLoans({ history, refreshKey, onChange }) {
  const { data, error, loading } = useData(
    history ? '/me/history' : '/me/loans',
    refreshKey,
  );
  const [query, setQuery] = useState('');
  const [busy, setBusy] = useState(null);
  const [message, setMessage] = useState('');
  const [actionError, setActionError] = useState('');
  const loans = (data?.loans || []).filter((l) =>
    l.book.title.toLowerCase().includes(query.toLowerCase()),
  );
  async function requestReturn(loan) {
    if (
      !window.confirm(
        `Dërgo kërkesë për kthimin e “${loan.book.title}”? Librin duhet ta dorëzosh te operatori.`,
      )
    )
      return;
    setBusy(loan.id);
    setActionError('');
    try {
      await api(`/loans/${loan.id}/return-request`, { method: 'POST' });
      setMessage('Kërkesa u dërgua. Dorëzo librin te operatori për konfirmim.');
      onChange();
    } catch (err) {
      setActionError(err.message);
    } finally {
      setBusy(null);
    }
  }
  return (
    <section className="card">
      <div className="section-heading">
        <div>
          <span className="eyebrow">BIBLIOTEKA JOTE</span>
          <h2>{history ? 'Historiku i huazimeve' : 'Librat që po lexon'}</h2>
        </div>
        <label>
          <span className="sr-only">Filtro sipas titullit</span>
          <input
            className="input"
            placeholder="Filtro sipas titullit…"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
          />
        </label>
      </div>
      {!history && (
        <p className="notice">
          Kërko kthimin këtu dhe dorëzo librin te operatori. Kërkesa nuk e
          ndryshon stokun dhe nuk e ndal vonesën; kthimi konfirmohet kur libri
          pranohet fizikisht.
        </p>
      )}
      {error && (
        <p className="error-box" role="alert">
          {error}
        </p>
      )}
      {loading ? (
        <p className="empty">Duke ngarkuar huazimet…</p>
      ) : (
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Libri</th>
                <th>Huazuar</th>
                <th>Afati i kthimit</th>
                {history && <th>Kthyer</th>}
                <th>Statusi</th>
                <th>Tarifa</th>
                {!history && <th>Kthimi</th>}
              </tr>
            </thead>
            <tbody>
              {loans.map((l) => (
                <tr key={l.id}>
                  <td>
                    <strong>{l.book.title}</strong>
                    <small className="block muted">{l.book.author}</small>
                  </td>
                  <td>{formatDate(l.borrowed_at)}</td>
                  <td>{formatDate(l.due_at)}</td>
                  {history && (
                    <td>
                      {l.returned_at
                        ? formatDate(l.returned_at)
                        : 'Ende te lexuesi'}
                    </td>
                  )}
                  <td>
                    <Status value={l.status} />
                  </td>
                  <td>€{l.fee}</td>
                  {!history && (
                    <td>
                      {l.return_requested_at ? (
                        <span className="badge badge-warn">
                          Kthimi u kërkua më {formatDate(l.return_requested_at)}
                        </span>
                      ) : (
                        <Button
                          loading={busy === l.id}
                          disabled={busy !== null}
                          onClick={() => requestReturn(l)}
                        >
                          Kërko kthimin
                        </Button>
                      )}
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
          {!loans.length && (
            <div className="empty">
              {history
                ? 'Ende nuk ke histori huazimesh.'
                : 'Nuk ke libra të huazuar. Zbulo katalogun për leximin e radhës.'}
            </div>
          )}
        </div>
      )}
      <Toast message={message} onClose={() => setMessage('')} />
      <Toast
        message={actionError}
        type="error"
        onClose={() => setActionError('')}
      />
    </section>
  );
}
