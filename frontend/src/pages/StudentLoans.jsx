import { formatDate } from '../formatDate.js';
import { useState } from 'react';
import useData from '../hooks/useData.js';
import Status from '../components/Status.jsx';

export default function StudentLoans({ history, refreshKey }) {
  const { data, error, loading } = useData(
    history ? '/me/history' : '/me/loans',
    refreshKey,
  );
  const [query, setQuery] = useState('');
  const loans = (data?.loans || []).filter((l) =>
    l.book.title.toLowerCase().includes(query.toLowerCase()),
  );
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
          Për ta kthyer librin, dorëzoje te operatori. Në rast vonese,
          penaliteti final llogaritet në ditën e kthimit.
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
    </section>
  );
}
