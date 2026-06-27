import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import axios from 'axios'
import { useAuth } from '../auth'

const statusColors = {
  open: 'bg-green-100 text-green-700',
  pending: 'bg-yellow-100 text-yellow-700',
  resolved: 'bg-blue-100 text-blue-700',
  closed: 'bg-gray-100 text-gray-500',
}

const priorityColors = {
  low: 'bg-gray-100 text-gray-600',
  medium: 'bg-blue-100 text-blue-600',
  high: 'bg-orange-100 text-orange-600',
  urgent: 'bg-red-100 text-red-600',
}

export default function Dashboard() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [tickets, setTickets] = useState([])
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState('')
  const [showNew, setShowNew] = useState(false)

  const fetchTickets = async () => {
    setLoading(true)
    const params = filter ? `?status=${filter}` : ''
    const res = await axios.get(`/api/tickets${params}`)
    setTickets(res.data.data || [])
    setLoading(false)
  }

  useEffect(() => { fetchTickets() }, [filter])

  const handleLogout = async () => {
    await logout()
    navigate('/')
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top nav */}
      <nav className="bg-white border-b border-gray-200">
        <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <span className="text-xl font-bold text-gray-900">PulseDesk</span>
            <span className="text-sm text-gray-400">|</span>
            <span className="text-sm text-gray-500">{user?.organization?.name}</span>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm text-gray-600">{user?.name}</span>
            <span className="text-xs px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 capitalize">{user?.role}</span>
            <button onClick={handleLogout} className="text-sm text-gray-500 hover:text-gray-900">Sign out</button>
          </div>
        </div>
      </nav>

      <div className="max-w-6xl mx-auto px-4 py-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-semibold text-gray-900">Tickets</h2>
          <div className="flex items-center gap-3">
            <select value={filter} onChange={e => setFilter(e.target.value)}
              className="rounded-md border border-gray-300 px-3 py-1.5 text-sm">
              <option value="">All statuses</option>
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
            <button onClick={() => setShowNew(true)}
              className="bg-indigo-600 text-white rounded-md px-4 py-1.5 text-sm font-medium hover:bg-indigo-700">
              + New Ticket
            </button>
          </div>
        </div>

        {loading ? (
          <p className="text-gray-400 py-8 text-center">Loading…</p>
        ) : tickets.length === 0 ? (
          <div className="text-center py-12 text-gray-400">
            <p>No tickets yet. Create one to get started.</p>
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                  <th className="text-left px-4 py-3 font-medium">Subject</th>
                  <th className="text-left px-4 py-3 font-medium">Status</th>
                  <th className="text-left px-4 py-3 font-medium">Priority</th>
                  <th className="text-left px-4 py-3 font-medium">Requester</th>
                  <th className="text-left px-4 py-3 font-medium">Updated</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {tickets.map(t => (
                  <tr key={t.id} onClick={() => navigate(`/tickets/${t.id}`)}
                    className="cursor-pointer hover:bg-gray-50">
                    <td className="px-4 py-3 font-medium text-gray-900">{t.subject}</td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${statusColors[t.status]}`}>
                        {t.status}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`inline-block px-2 py-0.5 rounded-full text-xs font-medium ${priorityColors[t.priority]}`}>
                        {t.priority}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-gray-600">{t.requester?.name}</td>
                    <td className="px-4 py-3 text-gray-400">{new Date(t.updated_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {showNew && <NewTicketModal onClose={() => setShowNew(false)} onCreated={() => { setShowNew(false); fetchTickets() }} />}
    </div>
  )
}

function NewTicketModal({ onClose, onCreated }) {
  const [form, setForm] = useState({ subject: '', description: '', priority: 'medium' })
  const [busy, setBusy] = useState(false)
  const [error, setError] = useState('')

  const submit = async (e) => {
    e.preventDefault()
    setBusy(true)
    setError('')
    try {
      await axios.post('/api/tickets', form)
      onCreated()
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to create ticket')
    } finally {
      setBusy(false)
    }
  }

  return (
    <div className="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
        <h3 className="text-lg font-semibold mb-4">New Ticket</h3>
        {error && <div className="text-sm text-red-600 bg-red-50 rounded p-3 mb-3">{error}</div>}
        <form onSubmit={submit} className="space-y-3">
          <input value={form.subject} onChange={e => setForm({ ...form, subject: e.target.value })}
            placeholder="Subject" required
            className="w-full rounded-md border border-gray-300 px-3 py-2" />
          <textarea value={form.description} onChange={e => setForm({ ...form, description: e.target.value })}
            placeholder="Describe the issue…" required rows="4"
            className="w-full rounded-md border border-gray-300 px-3 py-2" />
          <select value={form.priority} onChange={e => setForm({ ...form, priority: e.target.value })}
            className="rounded-md border border-gray-300 px-3 py-2">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
          <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={onClose} className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
            <button type="submit" disabled={busy}
              className="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
              {busy ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
