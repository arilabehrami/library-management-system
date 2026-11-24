import { useCallback, useEffect, useState } from 'react';
import { api } from '../api.js';
import Toast from '../components/Toast.jsx';
import Button from '../components/Button.jsx';

export default function Notifications({ refreshKey }) {
  const [items, setItems] = useState([]);
  const [busyId, setBusyId] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api('/notifications');
      setItems(data.notifications);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(load);
  }, [load, refreshKey]);

  async function markRead(id) {
    setBusyId(id);
    setError('');
    try {
      await api(`/notifications/${id}/read`, { method: 'POST' });
      await load();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyId(null);
    }
  }

  const unread = items.filter((n) => !n.is_read).length;

  return (
    <div className="card">
      <h2>
        Njoftimet{' '}
        {unread > 0 && <span className="badge badge-bad">{unread}</span>}
      </h2>
      {loading && (
        <p className="muted" role="status">
          Duke ngarkuar…
        </p>
      )}

      <table>
        <tbody>
          {items.map((n) => (
            <tr key={n.id} style={{ opacity: n.is_read ? 0.6 : 1 }}>
              <td>
                <span
                  className={`badge ${n.type === 'out_of_stock' || n.type === 'penalty_reminder' || n.type === 'overdue' ? 'badge-bad' : 'badge-warn'}`}
                >
                  {n.title}
                </span>
              </td>
              <td>{n.message}</td>
              <td style={{ fontSize: '0.8rem', color: '#6b7280' }}>
                {new Date(n.created_at).toLocaleString()}
              </td>
              <td>
                {!n.is_read && (
                  <Button
                    loading={busyId === n.id}
                    onClick={() => markRead(n.id)}
                  >
                    Lexova
                  </Button>
                )}
              </td>
            </tr>
          ))}
          {items.length === 0 && (
            <tr>
              <td colSpan="4">Asnjë njoftim.</td>
            </tr>
          )}
        </tbody>
      </table>

      <Toast message={error} type="error" onClose={() => setError('')} />
    </div>
  );
}
