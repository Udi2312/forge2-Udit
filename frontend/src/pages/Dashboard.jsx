import { useState, useEffect } from 'react'
import { useAuth } from '../lib/auth'
import { Link } from 'react-router-dom'
import api from '../lib/api'

export default function Dashboard() {
  const { user, logout } = useAuth()
  const [tickets, setTickets] = useState([])
  const [loading, setLoading] = useState(true)
  const [showForm, setShowForm] = useState(false)
  const [filters, setFilters] = useState({ status: '', priority: '', assignee_id: '', tag: '' })
  const [tags, setTags] = useState([])
  const [members, setMembers] = useState([])

  useEffect(() => {
    Promise.all([
      api.get('/api/tags'),
      api.get('/api/org/members'),
    ]).then(([t, m]) => {
      setTags(t.data)
      setMembers(m.data)
    })
  }, [])

  useEffect(() => {
    loadTickets()
  }, [filters])

  const loadTickets = async () => {
    setLoading(true)
    const params = {}
    Object.entries(filters).forEach(([key, val]) => {
      if (val) params[key] = val
    })
    const res = await api.get('/api/tickets', { params })
    setTickets(res.data.data || [])
    setLoading(false)
  }

  const statusColors = {
    open: 'bg-blue-100 text-blue-800',
    pending: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
  }

  const priorityColors = {
    low: 'bg-gray-100 text-gray-600',
    medium: 'bg-blue-100 text-blue-600',
    high: 'bg-orange-100 text-orange-600',
    urgent: 'bg-red-100 text-red-600',
  }

  const tagColors = {
    gray: 'bg-gray-100 text-gray-700',
    blue: 'bg-blue-100 text-blue-700',
    green: 'bg-green-100 text-green-700',
    yellow: 'bg-yellow-100 text-yellow-700',
    red: 'bg-red-100 text-red-700',
    purple: 'bg-purple-100 text-purple-700',
    orange: 'bg-orange-100 text-orange-700',
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16 items-center">
            <div className="flex items-center gap-8">
              <h1 className="text-xl font-bold text-gray-900">PulseDesk</h1>
              <Link to="/insights" className="text-sm text-indigo-600 hover:text-indigo-500">Insights</Link>
              <span className="text-sm text-gray-500">{user?.organization?.name}</span>
            </div>
            <div className="flex items-center gap-4">
              <span className="text-sm text-gray-600">{user?.name}</span>
              <button onClick={logout} className="text-sm text-indigo-600 hover:text-indigo-500">
                Sign out
              </button>
            </div>
          </div>
        </div>
      </nav>

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">Tickets</h2>
          <button
            onClick={() => setShowForm(!showForm)}
            className="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium"
          >
            New Ticket
          </button>
        </div>

        {showForm && <NewTicketForm tags={tags} onCreated={loadTickets} onCancel={() => setShowForm(false)} />}

        {/* Advanced Filters */}
        <div className="mb-4 bg-white p-4 rounded-lg shadow-sm border space-y-3">
          <div className="flex flex-wrap gap-2">
            <select
              value={filters.status}
              onChange={(e) => setFilters({ ...filters, status: e.target.value })}
              className="rounded-md border-gray-300 border p-2 text-sm"
            >
              <option value="">All Statuses</option>
              <option value="open">Open</option>
              <option value="pending">Pending</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>

            <select
              value={filters.priority}
              onChange={(e) => setFilters({ ...filters, priority: e.target.value })}
              className="rounded-md border-gray-300 border p-2 text-sm"
            >
              <option value="">All Priorities</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>

            <select
              value={filters.assignee_id}
              onChange={(e) => setFilters({ ...filters, assignee_id: e.target.value })}
              className="rounded-md border-gray-300 border p-2 text-sm"
            >
              <option value="">All Assignees</option>
              <option value="unassigned">Unassigned</option>
              {members.map((m) => (
                <option key={m.id} value={m.id}>{m.name}</option>
              ))}
            </select>

            <select
              value={filters.tag}
              onChange={(e) => setFilters({ ...filters, tag: e.target.value })}
              className="rounded-md border-gray-300 border p-2 text-sm"
            >
              <option value="">All Tags</option>
              {tags.map((t) => (
                <option key={t.id} value={t.name}>{t.name}</option>
              ))}
            </select>

            <input
              type="text"
              placeholder="Search..."
              value={filters.search || ''}
              onChange={(e) => setFilters({ ...filters, search: e.target.value })}
              className="flex-1 min-w-48 rounded-md border-gray-300 border p-2 text-sm"
            />

            {(filters.status || filters.priority || filters.assignee_id || filters.tag || filters.search) && (
              <button
                onClick={() => setFilters({ status: '', priority: '', assignee_id: '', tag: '', search: '' })}
                className="px-3 py-2 text-sm text-gray-500 hover:text-gray-700"
              >
                Clear
              </button>
            )}
          </div>
        </div>

        {loading ? (
          <p className="text-gray-500">Loading...</p>
        ) : tickets.length === 0 ? (
          <p className="text-gray-500">No tickets found.</p>
        ) : (
          <div className="bg-white shadow rounded-lg overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-50 border-b">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assignee</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tags</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {tickets.map((ticket) => (
                  <tr key={ticket.id} className="hover:bg-gray-50 cursor-pointer"
                    onClick={() => window.location.href = `/tickets/${ticket.id}`}>
                    <td className="px-6 py-4 text-sm text-gray-500">#{ticket.id}</td>
                    <td className="px-6 py-4 text-sm font-medium text-gray-900">{ticket.subject}</td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${statusColors[ticket.status]}`}>
                        {ticket.status}
                      </span>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${priorityColors[ticket.priority]}`}>
                        {ticket.priority}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600">
                      {ticket.assignee?.name || <span className="text-gray-400 italic">Unassigned</span>}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex gap-1 flex-wrap">
                        {ticket.tags?.map((tag) => (
                          <span key={tag.id} className={`px-2 py-0.5 rounded-full text-xs ${tagColors[tag.color] || tagColors.gray}`}>
                            {tag.name}
                          </span>
                        ))}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </main>
    </div>
  )
}

function NewTicketForm({ tags, onCreated, onCancel }) {
  const [form, setForm] = useState({ subject: '', description: '', priority: 'medium', tag_ids: [] })

  const toggleTag = (tagId) => {
    const current = form.tag_ids
    setForm({
      ...form,
      tag_ids: current.includes(tagId) ? current.filter((id) => id !== tagId) : [...current, tagId],
    })
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    await api.post('/api/tickets', form)
    setForm({ subject: '', description: '', priority: 'medium', tag_ids: [] })
    onCreated()
    onCancel()
  }

  return (
    <form onSubmit={handleSubmit} className="mb-6 bg-white p-4 rounded-lg shadow border">
      <input
        type="text"
        placeholder="Subject"
        required
        value={form.subject}
        onChange={(e) => setForm({ ...form, subject: e.target.value })}
        className="block w-full mb-2 rounded-md border-gray-300 border p-2"
      />
      <textarea
        placeholder="Description"
        required
        value={form.description}
        onChange={(e) => setForm({ ...form, description: e.target.value })}
        className="block w-full mb-2 rounded-md border-gray-300 border p-2"
        rows={3}
      />
      <select
        value={form.priority}
        onChange={(e) => setForm({ ...form, priority: e.target.value })}
        className="block w-full mb-2 rounded-md border-gray-300 border p-2"
      >
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      {tags.length > 0 && (
        <div className="mb-2">
          <p className="text-sm text-gray-600 mb-1">Tags:</p>
          <div className="flex gap-2 flex-wrap">
            {tags.map((t) => (
              <button
                key={t.id}
                type="button"
                onClick={() => toggleTag(t.id)}
                className={`px-2 py-1 rounded-full text-xs border ${
                  form.tag_ids.includes(t.id) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600'
                }`}
              >
                {t.name}
              </button>
            ))}
          </div>
        </div>
      )}
      <div className="flex gap-2 mt-2">
        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
          Create
        </button>
        <button type="button" onClick={onCancel} className="px-4 py-2 border rounded-md text-sm">
          Cancel
        </button>
      </div>
    </form>
  )
}
