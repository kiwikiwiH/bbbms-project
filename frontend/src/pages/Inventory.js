import { useEffect, useState } from 'react';
import { addInventory, getInventory } from '../services/api';
import { useAuth } from '../context/AuthContext';

const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

function todayISO() {
  return new Date().toISOString().slice(0, 10);
}

function expiryISO(days = 42) {
  const date = new Date();
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
}

export default function Inventory() {
  const { user } = useAuth();
  const canAdd = user?.role === 'admin' || user?.role === 'lab';
  const [items, setItems] = useState([]);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [form, setForm] = useState({
    unit_id: '',
    blood_group: 'O+',
    collection_date: todayISO(),
    expiry_date: expiryISO(),
    facility_name: 'Main Blood Bank',
  });

  const loadInventory = () => {
    getInventory()
      .then(({ data }) => setItems(data.items))
      .catch((err) => setError(err.response?.data?.error || 'Failed to load inventory'));
  };

  useEffect(() => {
    loadInventory();
  }, []);

  const handleChange = (event) => {
    setForm((prev) => ({ ...prev, [event.target.name]: event.target.value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setMessage('');
    try {
      const { data } = await addInventory(form);
      setMessage(data.message);
      if (data.blockchain?.warning) {
        setMessage(`${data.message} (blockchain: ${data.blockchain.warning})`);
      } else if (data.blockchain?.transaction_hash) {
        setMessage(`${data.message} — tx ${data.blockchain.transaction_hash.slice(0, 14)}...`);
      }
      setForm((prev) => ({
        ...prev,
        unit_id: '',
      }));
      loadInventory();
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to add inventory');
    }
  };

  return (
    <div className="stack">
      <section className="card">
        <h1>Blood inventory</h1>
        <p className="muted">Track blood units stored in the blood bank.</p>
      </section>

      {canAdd && (
        <section className="card">
          <h2>Add blood unit</h2>
          <form className="grid two form-grid" onSubmit={handleSubmit}>
            <label>
              Unit ID
              <input
                name="unit_id"
                value={form.unit_id}
                onChange={handleChange}
                placeholder="BBMS-001"
                required
              />
            </label>
            <label>
              Blood group
              <select name="blood_group" value={form.blood_group} onChange={handleChange}>
                {BLOOD_GROUPS.map((group) => (
                  <option key={group} value={group}>
                    {group}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Collection date
              <input
                type="date"
                name="collection_date"
                value={form.collection_date}
                onChange={handleChange}
                required
              />
            </label>
            <label>
              Expiry date
              <input
                type="date"
                name="expiry_date"
                value={form.expiry_date}
                onChange={handleChange}
                required
              />
            </label>
            <label className="full-width">
              Facility
              <input
                name="facility_name"
                value={form.facility_name}
                onChange={handleChange}
                required
              />
            </label>
            <div className="full-width">
              <button type="submit" className="btn-primary">
                Record unit
              </button>
            </div>
          </form>
        </section>
      )}

      {error && <div className="alert error">{error}</div>}
      {message && <div className="alert ok">{message}</div>}

      <section className="card">
        <h2>Current stock</h2>
        {items.length === 0 ? (
          <p className="muted">No blood units recorded yet.</p>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Unit ID</th>
                  <th>Group</th>
                  <th>Status</th>
                  <th>Collected</th>
                  <th>Expires</th>
                  <th>Facility</th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.inventory_id}>
                    <td>{item.unit_id}</td>
                    <td>{item.blood_group}</td>
                    <td><span className="badge neutral">{item.status}</span></td>
                    <td>{item.collection_date}</td>
                    <td>{item.expiry_date}</td>
                    <td>{item.facility_name}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  );
}
