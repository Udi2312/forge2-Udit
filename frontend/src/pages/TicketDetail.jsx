import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import { useAuth } from '../lib/auth'
import api from '../lib/api'

export default function TicketDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user, logout } = useAuth()
  const [ticket, setTicket] = useState(null)
  const [comments, setComments] = useState([])
  const [newComment, setNewComment] = useState('')
  const [isInternal, setIsInternal] = useState(false)
  const [members, setMembers] = useState([])
  const [tags, setTags] = useState([])
  const [showTagEditor, setShowTagEditor] = useState(false)

  useEffect(() => {
    Promise.all([
      api.get(`/api/tickets/${id}`),
      api.get(`/api/tickets/${id}/comments`),
      api.get('/api/org/members'),
      api.get('/api/tags'),
    ]).then(([t, c, m, tg]) => {
      setTicket(t.data)
      setComments(c.data)
      setMembers(m.data)
      setTags(tg.data)
    })
  }, [id])

  const handleAddComment = async (e) => {
    e.preventDefault()
    if (!newComment.trim()) return
    const res = await api.post(`/api/tickets/${id}/comments`, {
      body: newComment,
      is_internal: isInternal,
    })
    setComments([...comments, res.data])
    setNewComment('')
    setIsInternal(false)
  }

  const handleStatusChange = async (status) => {
    const res = await api.put(`/api/tickets/${id}`, { status })
    setTicket(res.data)
  }

  const handleAssign = async (assigneeId) => {
    const res = await api.put(`/api/tickets/${id}`, {
      assignee_id: assigneeId === '' ? null : parseInt(assigneeId),
    })
    setTicket(res.data)
  }

  const handlePriorityChange = async (priority) => {
    const res = await api.put(`/api/tickets/${id}`, { priority })
    setTicket(res.data)
  }

  const handleToggleTag = async (tagId) => {
    const currentIds = ticket.tags.map((t) => t.id)
    const newIds = currentIds.includes(tagId)
      ? currentIds.filter((tid) => tid !== tagId)
      : [...currentIds, tagId]
    const res = await api.put(`/api/tickets/${id}`, { tag_ids: newIds })
    setTicket(res.data)
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

  if (!ticket) return <div className="min-h-screen flex items-center justify-center">Loading...</div>

  return (
    <div className="min-h-screen bg-gray-50">
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 flex justify-between h-16 items-center">
          <div className="flex items-center gap-4">
            <Link to="/" className="text-indigo-600 text-sm">← Back</Link>
            <h1 className="text-xl font-bold text-gray-900">PulseDesk</h1>
          </div>
          <button onClick={() => { logout(); navigate('/login') }} className="text-sm text-indigo-600">
            Sign out
          </button>
        </div>
      </nav>

      <main className="max-w-5xl mx-auto px-4 py-8">
        <div className="grid grid-cols-3 gap-6">
          {/* Main column */}
          <div className="col-span-2 space-y-6">
            {/* Ticket */}
            <div className="bg-white rounded-lg shadow p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h2 className="text-2xl font-bold text-gray-900">{ticket.subject}</h2>
                  <p className="text-sm text-gray-500 mt-1">#{ticket.id} · by {ticket.requester?.name}</p>
                </div>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${statusColors[ticket.status]}`}>
                  {ticket.status}
                </span>
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

            {/* Comments */}
            <div className="bg-white rounded-lg shadow p-6">
              <h3 className="font-semibold text-gray-900 mb-4">Comments ({comments.length})</h3>
              <div className="space-y-4 mb-6">
                {comments.map((c) => (
                  <div key={c.id} className={`p-3 rounded-lg ${c.is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-gray-50'}`}>
                    <div className="flex justify-between items-center mb-1">
                      <span className="text-sm font-medium text-gray-900">{c.author?.name}</span>
                      {c.is_internal && (
                        <span className="text-xs bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded">Internal</span>
                      )}
                    </div>
                    <p className="text-sm text-gray-700 whitespace-pre-wrap">{c.body}</p>
                  </div>
                ))}
                {comments.length === 0 && <p className="text-gray-400 text-sm">No comments yet.</p>}
              </div>

              <form onSubmit={handleAddComment}>
                <textarea
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  placeholder="Write a comment..."
                  className="block w-full rounded-md border-gray-300 border p-2 mb-2"
                  rows={3}
                />
                <div className="flex items-center gap-4">
                  <label className="flex items-center gap-2 text-sm text-gray-600">
                    <input
                      type="checkbox"
                      checked={isInternal}
                      onChange={(e) => setIsInternal(e.target.checked)}
                      className="rounded"
                    />
                    Internal note
                  </label>
                  <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm">
                    Add comment
                  </button>
                </div>
              </form>
            </div>
          </div>

          {/* Sidebar */}
          <div className="col-span-1 space-y-4">
            {/* Assignment */}
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

            {/* Priority */}
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

            {/* Details */}
            <div className="bg-white rounded-lg shadow p-4 text-sm space-y-2">
              <h4 className="font-semibold text-gray-900">Details</h4>
              <div>
                <span className="text-gray-500">Requester:</span>{' '}
                <span className="text-gray-900">{ticket.requester?.name}</span>
              </div>
              <div>
                <span className="text-gray-500">Organization:</span>{' '}
                <span className="text-gray-900">{ticket.organization?.name || user?.organization?.name}</span>
              </div>
              <div>
                <span className="text-gray-500">Created:</span>{' '}
                <span className="text-gray-900">{new Date(ticket.created_at).toLocaleDateString()}</span>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}
