import { useEffect } from 'react';

export default function Toast({ message, type = 'ok', onClose }) {
  useEffect(() => {
    if (!message || type === 'error') return;
    const timer = setTimeout(onClose, 5000);
    return () => clearTimeout(timer);
  }, [message, type, onClose]);
  if (!message) return null;
  return (
    <div
      className={`toast toast-${type}`}
      role={type === 'error' ? 'alert' : 'status'}
    >
      <span>{message}</span>
      <button aria-label="Mbyll njoftimin" onClick={onClose}>
        ×
      </button>
    </div>
  );
}
