"use client";

import { useEffect, useRef, useState } from "react";
import { Send, MessageSquare, Loader2 } from "lucide-react";
import { cn } from "@/lib/cn";
import { apiFetch } from "@/lib/api-client";
import { formatDateTime } from "@/lib/format";
import { useToast } from "@/components/ui/Toast";

type Conversation = {
  id: string;
  guestName: string | null;
  status: string;
  updatedAt: string;
  lastMessage: { content: string; kind: string; sender: string } | null;
  unreadCount: number;
};

type Message = {
  id: string;
  sender: "guest" | "admin";
  kind: "text" | "image" | "audio";
  content: string;
  createdAt: string;
};

export function AdminChatInbox() {
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [activeId, setActiveId] = useState<string | null>(null);
  const [messages, setMessages] = useState<Message[]>([]);
  const [text, setText] = useState("");
  const [sending, setSending] = useState(false);
  const { push } = useToast();
  const listRef = useRef<HTMLDivElement>(null);

  async function loadConversations() {
    try {
      const data = await apiFetch<{ conversations: Conversation[] }>("/api/admin/chat/conversations");
      setConversations(data.conversations);
      if (!activeId && data.conversations.length > 0) setActiveId(data.conversations[0].id);
    } catch {
      /* noop */
    }
  }

  async function loadMessages(id: string) {
    try {
      const data = await apiFetch<{ messages: Message[] }>(`/api/admin/chat/conversations/${id}/messages`);
      setMessages(data.messages);
      requestAnimationFrame(() => listRef.current?.scrollTo({ top: listRef.current.scrollHeight }));
    } catch {
      /* noop */
    }
  }

  useEffect(() => {
    // eslint-disable-next-line react-hooks/set-state-in-effect -- initial fetch on mount
    loadConversations();
    const interval = setInterval(loadConversations, 5000);
    return () => clearInterval(interval);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (!activeId) return;
    // eslint-disable-next-line react-hooks/set-state-in-effect -- fetch thread when selection changes
    loadMessages(activeId);
    const interval = setInterval(() => loadMessages(activeId), 4000);
    return () => clearInterval(interval);
  }, [activeId]);

  async function handleSend() {
    const value = text.trim();
    if (!value || !activeId) return;
    setSending(true);
    try {
      const data = await apiFetch<{ message: Message }>(`/api/admin/chat/conversations/${activeId}/messages`, {
        method: "POST",
        body: JSON.stringify({ kind: "text", content: value }),
      });
      setMessages((prev) => [...prev, data.message]);
      setText("");
      loadConversations();
    } catch (err) {
      push(err instanceof Error ? err.message : "تعذر الإرسال", "error");
    } finally {
      setSending(false);
    }
  }

  return (
    <div className="grid grid-cols-1 gap-4 overflow-hidden rounded-2xl border border-border lg:h-[70vh] lg:grid-cols-[280px_1fr]">
      <div className="border-b border-border lg:overflow-y-auto lg:border-b-0 lg:border-l">
        {conversations.length === 0 ? (
          <p className="p-6 text-center text-sm text-muted">لا توجد محادثات بعد</p>
        ) : (
          conversations.map((c) => (
            <button
              key={c.id}
              onClick={() => setActiveId(c.id)}
              className={cn(
                "flex w-full items-start gap-2 border-b border-border p-3.5 text-start transition-colors hover:bg-surface-muted",
                activeId === c.id && "bg-primary-soft"
              )}
            >
              <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-surface-muted text-muted">
                <MessageSquare className="size-4" />
              </span>
              <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between">
                  <p className="truncate text-sm font-semibold">{c.guestName || "زائر"}</p>
                  {c.unreadCount > 0 && (
                    <span className="flex size-5 items-center justify-center rounded-full bg-danger text-[10px] font-bold text-white">
                      {c.unreadCount}
                    </span>
                  )}
                </div>
                <p className="truncate text-xs text-muted">
                  {c.lastMessage ? (c.lastMessage.kind === "text" ? c.lastMessage.content : "مرفق") : "لا رسائل"}
                </p>
              </div>
            </button>
          ))
        )}
      </div>

      <div className="flex flex-col">
        {!activeId ? (
          <div className="flex flex-1 items-center justify-center text-sm text-muted">اختر محادثة لعرضها</div>
        ) : (
          <>
            <div ref={listRef} className="flex-1 space-y-3 overflow-y-auto p-4">
              {messages.map((m) => (
                <div key={m.id} className={cn("flex", m.sender === "admin" ? "justify-end" : "justify-start")}>
                  <div
                    className={cn(
                      "max-w-[75%] rounded-2xl px-3.5 py-2 text-sm",
                      m.sender === "admin" ? "bg-primary text-primary-foreground" : "bg-surface-muted"
                    )}
                  >
                    {m.kind === "text" && <p className="whitespace-pre-wrap break-words">{m.content}</p>}
                    {m.kind === "image" && (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={m.content} alt="صورة" className="max-h-56 rounded-xl object-cover" />
                    )}
                    {m.kind === "audio" && <audio controls src={m.content} />}
                    <p className="mt-1 text-[10px] opacity-60">{formatDateTime(m.createdAt)}</p>
                  </div>
                </div>
              ))}
            </div>
            <div className="flex items-center gap-2 border-t border-border p-3">
              <input
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleSend()}
                placeholder="اكتب ردك…"
                className="h-10 flex-1 rounded-full border border-border bg-background px-4 text-sm outline-none focus:border-primary"
              />
              <button
                onClick={handleSend}
                disabled={sending || !text.trim()}
                className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground disabled:opacity-50"
              >
                {sending ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
