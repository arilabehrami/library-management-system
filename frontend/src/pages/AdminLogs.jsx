import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

const ENTITY_TYPES = ['', 'Loan', 'Book', 'Penalty', 'User', 'SystemSetting'];

export default function AdminLogs() {
  const [logs, setLogs] = useState([]);
  const [entityType, setEntityType] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = new URLSearchParams({ limit: '100' });
      if (entityType) params.set('entity_type', entityType);
      const data = await api(`/admin/logs?${params.toString()}`);
      setLogs(data.logs);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, [entityType]);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load]);

  return (
    <div className="card">
      <h2>Logjet e sistemit</h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}

      <div
        style={{
          display: 'flex',
          gap: 8,
          alignItems: 'center',
          marginBottom: 12,
        }}
      >
        <select
          aria-label="Lloji i objektit"
          className="input"
          style={{ maxWidth: 200 }}
          value={entityType}
          onChange={(e) => setEntityType(e.target.value)}
        >
          {ENTITY_TYPES.map((t) => (
            <option key={t} value={t}>
              {t || 'Të gjitha'}
            </option>
          ))}
        </select>
        <Button className="btn-secondary" onClick={load}>
          Rifresko
        </Button>
      </div>

      <table>
        <thead>
          <tr>
            <th>Koha</th>
            <th>Aktori</th>
            <th>Veprimi</th>
            <th>Objekti</th>
            <th>Detajet</th>
          </tr>
        </thead>
        <tbody>
          {logs.map((l) => (
            <tr key={l.id}>
              <td>{new Date(l.timestamp).toLocaleString()}</td>
              <td>#{l.actor_id}</td>
              <td>
                <span className="badge badge-warn">{l.action_type}</span>
              </td>
              <td>
                {l.entity_type} #{l.entity_id}
              </td>
              <td style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                {l.details}
              </td>
            </tr>
          ))}
          {logs.length === 0 && (
            <tr>
              <td colSpan="5">Asnjë log.</td>
            </tr>
          )}
        </tbody>
      </table>

      <Toast message={error} type="error" onClose={() => setError('')} />
    </div>
  );
}
