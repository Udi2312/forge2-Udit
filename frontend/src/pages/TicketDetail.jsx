import { useState, useEffect, useCallback } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import api from '../lib/api'
import NotificationBell from '../components/NotificationBell'

export default function TicketDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, logout } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [messages, setMessages] = useState([])
  const [activity, setActivity] = useState([])
  const [members, setMembers] = useState([])
  const [tags, setTags] = useState([])
  const [newMessage, setNewMessage] = useState('')
  const [isInternal, setIsInternal] = useState(false)
  const [showTagEditor, setShowTagEditor] = useState(false)
  const [activeTab, setActiveTab] = useState('conversation')

  const fetchData = useCallback(() => {
    Promise.all([
      api.get(`/api/tickets/${id}`),
      api.get(`/api/tickets/${id}/messages`),
      api.get(`/api/tickets/${id}/activity`),
      api.get('/api/org/members'),
      api.get('/api/tags'),
    ]).then(([t, m, a, mems, tg]) => {
      setTicket(t.data)
      setMessages(m.data)
      setActivity(a.data)
      setMembers(mems.data)
      setTags(tg.data)
    }).catch(() => {})
  }, [id])

  useEffect(() => { fetchData() }, [fetchData])

  const handleMessage = async (e) => {
    e.preventDefault()
    if (!newMessage.trim()) return
    const res = await api.post(`/api/tickets/${id}/messages`, {
      body: newMessage,
      is_internal: isInternal,
    })
    setMessages([...messages, res.data])
    setNewMessage('')
    setIsInternal(false)
    // Refresh activity
    const a = await api.get(`/api/tickets/${id}/activity`)
    setActivity(a.data)
  }

  const handleStatusChange = async (status) => {
    const res = await api.put(`/api/tickets/${id}`, { status })
    setTicket(res.data)
    const a = await api.get(`/api/tickets/${id}/activity`)
    setActivity(a.data)
  }

  const handleAssign = async (assigneeId) => {
    const res = await api.put(`/api/tickets/${id}`, {
      assignee_id: assigneeId === '' ? null : parseInt(assigneeId),
    })
    setTicket(res.data)
    const a = await api.get(`/api/tickets/${id}/activity`)
    setActivity(a.data)
  }

  const handlePriorityChange = async (priority) => {
    const res = await api.put(`/api/tickets/${id}`, { priority })
    setTicket(res.data)
    const a = await api.get(`/api/tickets/${id}/activity`)
    setActivity(a.data)
  }

  const handleToggleTag = async (tagId) => {
    const currentIds = ticket.tags.map((t) => t.id)
    const newIds = currentIds.includes(tagId)
      ? currentIds.filter((tid) => tid !== tagId)
      : [...currentIds, tagId]
    const res = await api.put(`/api/tickets/${id}`, { tag_ids: newIds })
    setTicket(res.data)
    const a = await api.get(`/api/tickets/${id}/activity`)
    setActivity(a.data)
  }

  const canSeeInternal = user?.role === 'admin' || user?.role === 'agent'

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

  const eventLabels = {
    created: 'Ticket created',
    status_changed: 'Status changed',
    priority_changed: 'Priority changed',
    assigned: 'Assignee changed',
    message_sent: 'Message sent',
    tagged: 'Tags updated',
    updated: 'Ticket updated',
  }

  if (!ticket) return <div className="min-h-screen flex items-center justify-center">Loading...</div>

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
          <div className="flex items-center gap-4">
            <Link to="/" className="text-indigo-600 text-sm">← Back</Link>
            <Link to="/insights" className="text-indigo-600 text-sm">Insights</Link>
            <h1 className="text-xl font-bold text-gray-900">PulseDesk</h1>
          </div>
          <div className="flex items-center gap-3">
            <NotificationBell />
            <button onClick={() => { logout(); navigate('/login') }} className="text-sm text-indigo-600">
              Sign out
            </button>
          </div>
        </div>
      </nav>

      <main className="max-w-6xl mx-auto px-4 py-8">
        <div className="grid grid-cols-3 gap-6">
          {/* Main column */}
          <div className="col-span-2 space-y-6">
            {/* Ticket header */}
            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900">{ticket.subject}</h2>
                  <p className="text-sm text-gray-500 mt-1">#{ticket.id} · by {ticket.requester?.name}</p>
                </div>
                <div className="flex flex-col items-end gap-2">
                  <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[ticket.status]}`}>
                    {ticket.status}
                  </span>
                  {ticket.sla && ticket.sla.status && ticket.sla.status !== 'none' && (
                    <div className="text-xs text-right">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        ticket.sla.status === 'breached' ? 'bg-red-100 text-red-700' :
                        ticket.sla.status === 'warning' ? 'bg-yellow-100 text-yellow-700' :
                        ticket.sla.status === 'met' ? 'bg-gray-100 text-gray-500' :
                        'bg-green-100 text-green-700'
                      }`}>
                        {ticket.sla.status === 'breached' ? '🔴 SLA Breached' :
                         ticket.sla.status === 'warning' ? '⚠ SLA Risk' :
                         ticket.sla.status === 'met' ? '✓ SLA Met' :
                         '✓ SLA On Track'}
                      </span>
                      {ticket.sla.response_due && (
                        <p className="text-gray-400 mt-1">
                          Response: {new Date(ticket.sla.response_due).toLocaleString()}
                        </p>
                      )}
                      {ticket.sla.resolution_due && (
                        <p className="text-gray-400">
                          Resolution: {new Date(ticket.sla.resolution_due).toLocaleString()}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              </div>
              <p className="text-gray-700 whitespace-pre-wrap">{ticket.description}</p>

              {/* Tags */}
              <div className="mt-4 flex items-center gap-2 flex-wrap">
                {ticket.tags?.map((tag) => (
                  <span key={tag.id} className={`px-2 py-0.5 rounded-full text-xs ${tagColors[tag.color] || tagColors.gray}`}>
                    {tag.name}
                  </span>
                ))}
                <button
                  onClick={() => setShowTagEditor(!showTagEditor)}
                  className="text-xs text-indigo-600 hover:underline"
                >
                  {showTagEditor ? 'Done' : '+ Edit tags'}
                </button>
              </div>

              {/* Tag editor */}
              {showTagEditor && (
                <div className="mt-2 flex gap-2 flex-wrap p-3 bg-gray-50 rounded-md">
                  {tags.map((t) => (
                    <button
                      key={t.id}
                      onClick={() => handleToggleTag(t.id)}
                      className={`px-2 py-1 rounded-full text-xs border ${
                        ticket.tags?.some((tt) => tt.id === t.id)
                          ? 'bg-indigo-600 text-white border-indigo-600'
                          : 'bg-white text-gray-600'
                      }`}
                    >
                      {t.name}
                    </button>
                  ))}
                  {tags.length === 0 && <p className="text-sm text-gray-400">No tags in your org yet.</p>}
                </div>
              )}

              {/* Status buttons */}
              <div className="mt-4 flex gap-2">
                {['open', 'pending', 'resolved', 'closed'].map((s) => (
                  <button
                    key={s}
                    onClick={() => handleStatusChange(s)}
                    className={`px-3 py-1 rounded-md text-xs font-medium border ${
                      ticket.status === s ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600'
                    }`}
                  >
                    {s}
                  </button>
                ))}
              </div>
            </div>

            {/* Tabs */}
            <div className="bg-white rounded-lg shadow">
              <div className="flex border-b">
                <button
                  onClick={() => setActiveTab('conversation')}
                  className={`px-6 py-3 text-sm font-medium border-b-2 ${
                    activeTab === 'conversation' ? 'border-indigo-600 text-indigo-600' : 'text-gray-500'
                  }`}
                >
                  Conversation ({messages.length})
                </button>
                <button
                  onClick={() => setActiveTab('activity')}
                  className={`px-6 py-3 text-sm font-medium border-b-2 ${
                    activeTab === 'activity' ? 'border-indigo-600 text-indigo-600' : 'text-gray-500'
                  }`}
                >
                  Activity ({activity.length})
                </button>
              </div>

              {/* Conversation Tab */}
              {activeTab === 'conversation' && (
                <div className="p-6">
                  <div className="space-y-4 mb-6 max-h-96 overflow-y-auto">
                    {messages.map((m) => (
                      <div
                        key={m.id}
                        className={`p-3 rounded-lg ${
                          m.is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50'
                        }`}
                      >
                        <div className="flex justify-between items-center mb-1">
                          <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-gray-900">{m.user?.name}</span>
                            {m.is_internal && (
                              <span className="text-xs bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded">
                                🔒 Internal
                              </span>
                            )}
                          </div>
                          <span className="text-xs text-gray-400">
                            {new Date(m.created_at).toLocaleString()}
                          </span>
                        </div>
                        <p className="text-sm text-gray-700 whitespace-pre-wrap">{m.body}</p>
                      </div>
                    ))}
                    {messages.length === 0 && (
                      <p className="text-gray-400 text-sm text-center py-4">No messages yet. Start the conversation below.</p>
                    )}
                  </div>

                  <form onSubmit={handleMessage}>
                    <textarea
                      value={newMessage}
                      onChange={(e) => setNewMessage(e.target.value)}
                      placeholder="Write a reply..."
                      className="block w-full rounded-md border-gray-300 border p-2 mb-2"
                      rows={3}
                    />
                    <div className="flex items-center gap-4">
                      {canSeeInternal && (
                        <label className="flex items-center gap-2 text-sm text-gray-600">
                          <input
                            type="checkbox"
                            checked={isInternal}
                            onChange={(e) => setIsInternal(e.target.checked)}
                            className="rounded"
                          />
                          Internal note
                        </label>
                      )}
                      <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
                        Send
                      </button>
                    </div>
                  </form>
                </div>
              )}

              {/* Activity Tab */}
              {activeTab === 'activity' && (
                <div className="p-6">
                  <div className="space-y-3">
                    {activity.map((log) => (
                      <div key={log.id} className="flex items-start gap-3 pb-3 border-b last:border-0">
                        <div className="w-2 h-2 rounded-full bg-indigo-400 mt-2 shrink-0"></div>
                        <div className="flex-1">
                          <div className="flex justify-between items-center">
                            <span className="text-sm font-medium text-gray-900">
                              {log.user?.name || 'System'}
                            </span>
                            <span className="text-xs text-gray-400">
                              {new Date(log.created_at).toLocaleString()}
                            </span>
                          </div>
                          <p className="text-sm text-gray-600">
                            {eventLabels[log.event] || log.event}
                            {log.field && log.old_value !== null && (
                              <span className="text-gray-400">
                                {' '}— {log.field}: <span className="line-through">{log.old_value}</span> → {log.new_value}
                              </span>
                            )}
                            {log.field && log.old_value === null && log.new_value && (
                              <span className="text-gray-400">
                                {' '}— {log.field} → {log.new_value}
                              </span>
                            )}
                          </p>
                        </div>
                      </div>
                    ))}
                    {activity.length === 0 && (
                      <p className="text-gray-400 text-sm text-center py-4">No activity recorded yet.</p>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Sidebar */}
          <div className="col-span-1 space-y-4">
            <div className="bg-white rounded-lg shadow p-4">
              <h4 className="text-sm font-semibold text-gray-900 mb-3">Assignee</h4>
              <select
                value={ticket.assignee_id || ''}
                onChange={(e) => handleAssign(e.target.value)}
                className="block w-full rounded-md border-gray-300 border p-2 text-sm"
              >
                <option value="">Unassigned</option>
                {members.map((m) => (
                  <option key={m.id} value={m.id}>{m.name}</option>
                ))}
              </select>
              <p className="text-xs text-gray-400 mt-1">{ticket.assignee?.email}</p>
            </div>

            <div className="bg-white rounded-lg shadow p-4">
              <h4 className="text-sm font-semibold text-gray-900 mb-3">Priority</h4>
              <div className="flex gap-1 flex-wrap">
                {['low', 'medium', 'high', 'urgent'].map((p) => (
                  <button
                    key={p}
                    onClick={() => handlePriorityChange(p)}
                    className={`px-2 py-1 rounded-md text-xs font-medium border ${
                      ticket.priority === p ? priorityColors[p] + ' border-current' : 'text-gray-500'
                    }`}
                  >
                    {p}
                  </button>
                ))}
              </div>
            </div>

            <div className="bg-white rounded-lg shadow p-4 text-sm space-y-2">
              <h4 className="font-semibold text-gray-900">Details</h4>
              <div>
                <span className="text-gray-500">Requester:</span>{' '}
                <span className="text-gray-900">{ticket.requester?.name}</span>
              </div>
              <div>
                <span className="text-gray-500">Created:</span>{' '}
                <span className="text-gray-900">{new Date(ticket.created_at).toLocaleString()}</span>
              </div>
              <div>
                <span className="text-gray-500">Updated:</span>{' '}
                <span className="text-gray-900">{new Date(ticket.updated_at).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}
