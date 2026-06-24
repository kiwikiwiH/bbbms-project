import { useEffect, useState } from 'react';
import { createRequest, getRequests } from '../services/api';
import { useAuth } from '../context/AuthContext';

const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

export default function Requests() {
  const { user } = useAuth();
  const canCreate = user?.role === 'hospital';
  const [requests, setRequests] = useState([]);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [form, setForm] = useState({
    blood_group: 'O+',
    quantity_requested: 1,
    urgency: 'normal',
    notes: '',
  });

  const loadRequests = () => {
    getRequests()
      .then(({ data }) => setRequests(data.requests))
      .catch((err) => setError(err.response?.data?.error || 'Failed to load requests'));
  };

  useEffect(() => {
    loadRequests();
  }, []);

  const handleChange = (event) => {
    setForm((prev) => ({ ...prev, [event.target.name]: event.target.value }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setError('');
    setMessage('');
    try {
      await createRequest({
        ...form,
        quantity_requested: Number(form.quantity_requested),
      });
      setMessage('Blood request submitted.');
      setForm({ blood_group: 'O+', quantity_requested: 1, urgency: 'normal', notes: '' });
      loadRequests();
    } catch (err) {
      setError(err.response?.data?.error || 'Failed to create request');
    }
  };

  return (
    <div className="stack">
      <section className="card">
        <h1>Blood requests</h1>
        <p className="muted">Hospitals request blood units from the blood bank.</p>
      </section>

      {canCreate && (
        <section className="card">
          <h2>New request</h2>
          <form className="grid two form-grid" onSubmit={handleSubmit}>
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
              Quantity
              <input
                type="number"
                min="1"
                name="quantity_requested"
                value={form.quantity_requested}
                onChange={handleChange}
                required
              />
            </label>
            <label>
              Urgency
              <select name="urgency" value={form.urgency} onChange={handleChange}>
                <option value="normal">Normal</option>
                <option value="emergency">Emergency</option>
              </select>
            </label>
            <label>
              Notes
              <input name="notes" value={form.notes} onChange={handleChange} />
            </label>
            <div className="full-width">
              <button type="submit" className="btn-primary">
                Submit request
              </button>
            </div>
          </form>
        </section>
      )}

      {error && <div className="alert error">{error}</div>}
      {message && <div className="alert ok">{message}</div>}

      <section className="card">
        <h2>All requests</h2>
        {requests.length === 0 ? (
          <p className="muted">No requests yet.</p>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Hospital</th>
                  <th>Group</th>
                  <th>Qty</th>
                  <th>Urgency</th>
                  <th>Status</th>
                  <th>Created</th>
                </tr>
              </thead>
              <tbody>
                {requests.map((request) => (
                  <tr key={request.request_id}>
                    <td>{request.request_id}</td>
                    <td>{request.hospital_name}</td>
                    <td>{request.blood_group}</td>
                    <td>{request.quantity_requested}</td>
                    <td>
                      <span className={`badge ${request.urgency === 'emergency' ? 'warn' : 'neutral'}`}>
                        {request.urgency}
                      </span>
                    </td>
                    <td>{request.status}</td>
                    <td>{request.created_at}</td>
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
