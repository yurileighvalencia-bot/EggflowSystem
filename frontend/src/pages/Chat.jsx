import { useState, useEffect, useRef } from 'react';
import { useAuth } from '../context/AuthContext';
import axios from 'axios';
import { io } from 'socket.io-client';

function Chat() {
  const { user } = useAuth();
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [loading, setLoading] = useState(true);
  const messagesEndRef = useRef(null);
  const socketRef = useRef(null);

  useEffect(() => {
    fetchMessages();
    initSocket();

    return () => {
      if (socketRef.current) {
        socketRef.current.disconnect();
      }
    };
  }, []);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const initSocket = () => {
    socketRef.current = io('http://localhost:5000');
    
    socketRef.current.on('connect', () => {
      console.log('Connected to socket server');
      socketRef.current.emit('join', user._id);
    });

    socketRef.current.on('newMessage', (message) => {
      setMessages(prev => [...prev, message]);
    });
  };

  const fetchMessages = async () => {
    try {
      const { data } = await axios.get('/api/messages');
      setMessages(data);
    } catch (error) {
      console.error('Failed to load messages:', error);
    } finally {
      setLoading(false);
    }
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    
    if (!newMessage.trim()) return;

    try {
      const { data } = await axios.post('/api/messages', {
        message: newMessage,
        recipient: null // For now, messages go to all
      });

      setMessages(prev => [...prev, data]);
      setNewMessage('');

      // Emit socket event for real-time update
      if (socketRef.current) {
        socketRef.current.emit('sendMessage', {
          recipientId: data.recipient?._id,
          message: data
        });
      }
    } catch (error) {
      console.error('Failed to send message:', error);
    }
  };

  const formatMessageTime = (timestamp) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // If today, show time
    if (diff < 86400000) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    // If this week, show day and time
    if (diff < 604800000) {
      return date.toLocaleDateString([], { weekday: 'short', hour: '2-digit', minute: '2-digit' });
    }
    // Otherwise show date and time
    return date.toLocaleString([], { 
      month: 'short', 
      day: 'numeric', 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  };

  if (loading) {
    return <div className="container"><div className="loading">Loading chat...</div></div>;
  }

  return (
    <div className="container">
      <h1>Chat & Communication</h1>

      <div className="card">
        <div className="chat-container">
          <div className="chat-messages">
            {messages.length === 0 ? (
              <div className="empty-state">No messages yet. Start a conversation!</div>
            ) : (
              messages.map((msg) => {
                const isMine = msg.sender?._id === user._id;
                const isSystem = msg.isSystemMessage;
                
                return (
                  <div
                    key={msg._id}
                    className={`message ${
                      isSystem ? 'message-system' : isMine ? 'message-sent' : 'message-received'
                    }`}
                  >
                    {!isMine && !isSystem && (
                      <div style={{ fontSize: '12px', fontWeight: 'bold', marginBottom: '5px' }}>
                        {msg.sender?.name} ({msg.sender?.role})
                      </div>
                    )}
                    <div>{msg.message}</div>
                    <div style={{ 
                      fontSize: '11px', 
                      color: '#666', 
                      marginTop: '5px',
                      textAlign: isMine ? 'right' : 'left'
                    }}>
                      {formatMessageTime(msg.createdAt)}
                    </div>
                  </div>
                );
              })
            )}
            <div ref={messagesEndRef} />
          </div>

          <form onSubmit={handleSendMessage}>
            <div className="chat-input">
              <input
                type="text"
                value={newMessage}
                onChange={(e) => setNewMessage(e.target.value)}
                placeholder="Type your message..."
                style={{ flex: 1 }}
              />
              <button type="submit" className="btn btn-primary">
                Send
              </button>
            </div>
          </form>
        </div>
      </div>

      <div className="card">
        <h3>Chat Guidelines</h3>
        <ul style={{ paddingLeft: '20px', lineHeight: '1.8' }}>
          <li>Customers can chat with managers for inquiries and support</li>
          <li>Staff can communicate with managers for coordination</li>
          <li>System notifications will appear here automatically</li>
          <li>Messages are sent in real-time when both parties are online</li>
        </ul>
      </div>
    </div>
  );
}

export default Chat;
