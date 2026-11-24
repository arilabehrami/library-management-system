import { formatDate } from '../formatDate.js';
import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { clearToken } from '../api.js';
import StudentCatalog from './StudentCatalog.jsx';
import StudentLoans from './StudentLoans.jsx';
import MyPenalties from './MyPenalties.jsx';
import OperatorBooks from './OperatorBooks.jsx';
import OperatorLoans from './OperatorLoans.jsx';
import AdminUsers from './AdminUsers.jsx';
import AdminSettings from './AdminSettings.jsx';
import AdminLogs from './AdminLogs.jsx';
import Notifications from './Notifications.jsx';

export default function Dashboard() {
  const navigate = useNavigate();
  let user;
  try {
    user = JSON.parse(localStorage.getItem('lms_user') || '{}');
  } catch {
    user = {};
  }
  const student = user.role === 'student';
  const admin = user.role === 'super_admin';
  const tabs = student
    ? [
        ['catalog', 'Katalogu'],
        ['loans', 'Huazimet e mia'],
        ['history', 'Historiku'],
        ['penalties', 'Penalitetet'],
      ]
    : [
        ['loans', 'Huazimet'],
        ['books', 'Librat'],
      ];
  if (admin)
    tabs.push(
      ['users', 'Llogaritë'],
      ['settings', 'Cilësimet'],
      ['logs', 'Logjet'],
    );
  tabs.push(['notifications', 'Njoftimet']);
  const [tab, setTab] = useState(student ? 'catalog' : 'loans');
  const [refreshKey, setRefreshKey] = useState(0);
  const bump = () => setRefreshKey((k) => k + 1);
  function logout() {
    clearToken();
    navigate('/login');
  }
  return (
    <div className="app-shell">
      <aside className="sidebar">
        <a className="brand" href="/">
          <span className="brand-mark">B</span> Biblioteka
        </a>
        <span className="nav-label">HAPËSIRA JOTE</span>
        <nav aria-label="Navigimi kryesor">
          {tabs.map(([id, label]) => (
            <button
              key={id}
              className={tab === id ? 'nav-item selected' : 'nav-item'}
              aria-current={tab === id ? 'page' : undefined}
              onClick={() => setTab(id)}
            >
              <span className="nav-symbol" aria-hidden="true">
                {id === 'catalog' || id === 'books'
                  ? '▤'
                  : id === 'notifications'
                    ? '◉'
                    : '▦'}
              </span>
              {label}
            </button>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <div className="avatar">{user.name?.slice(0, 1) || 'B'}</div>
          <div>
            <strong>{user.name}</strong>
            <p>{student ? 'Student' : admin ? 'Super Admin' : 'Operator'}</p>
          </div>
          <button onClick={logout} className="logout" title="Dil nga llogaria">
            Dil
          </button>
        </div>
      </aside>
      <main className="main-content">
        <header className="topbar">
          <span>Biblioteka / {tabs.find((t) => t[0] === tab)?.[1]}</span>
          <span>{formatDate(new Date())}</span>
        </header>
        <section className="welcome">
          <div>
            <span className="eyebrow">
              {student ? 'LEXO. ZBULO. MËSO.' : 'MENAXHIMI I BIBLIOTEKËS'}
            </span>
            <h1>
              {student
                ? `Mirë se erdhe, ${user.name?.split(' ')[0] || ''}.`
                : 'Çdo libër, në vendin e vet.'}
            </h1>
            <p>
              {student
                ? 'Gjej leximin e radhës dhe mbaji huazimet nën kontroll.'
                : 'Menaxho librat, kthimet dhe lexuesit nga një hapësirë.'}
            </p>
          </div>
          <div className="welcome-books" aria-hidden="true">
            ▤<span>Hap një kapitull të ri.</span>
          </div>
        </section>
        {tab === 'catalog' && (
          <StudentCatalog refreshKey={refreshKey} onChange={bump} />
        )}
        {student && (tab === 'loans' || tab === 'history') && (
          <StudentLoans
            key={tab}
            history={tab === 'history'}
            onChange={bump}
            refreshKey={refreshKey}
          />
        )}
        {tab === 'penalties' && (
          <MyPenalties onChange={bump} refreshKey={refreshKey} />
        )}
        {!student && tab === 'loans' && (
          <OperatorLoans onChange={bump} refreshKey={refreshKey} />
        )}
        {tab === 'books' && <OperatorBooks onChange={bump} />}
        {tab === 'users' && <AdminUsers />}
        {tab === 'settings' && <AdminSettings />}
        {tab === 'logs' && <AdminLogs />}
        {tab === 'notifications' && <Notifications refreshKey={refreshKey} />}
        <footer>Biblioteka · Më shumë hapësirë për dije.</footer>
      </main>
    </div>
  );
}
