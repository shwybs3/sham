"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import { AnimatePresence, motion } from "framer-motion";
import {
  MessageCircle,
  X,
  Send,
  Paperclip,
  Mic,
  Square,
  Loader2,
  HeadphonesIcon,
} from "lucide-react";
import { apiFetch } from "@/lib/api-client";
import { formatDateTime } from "@/lib/format";
import { cn } from "@/lib/cn";
import { useToast } from "@/components/ui/Toast";

type ChatMessage = {
  id: string;
  sender: "guest" | "admin";
  kind: "text" | "image" | "audio";
  content: string;
  createdAt: string;
};

export function ChatWidget() {
  const [open, setOpen] = useState(false);
  const [conversationId, setConversationId] = useState<string | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [text, setText] = useState("");
  const [sending, setSending] = useState(false);
  const [recording, setRecording] = useState(false);
  const [uploading, setUploading] = useState(false);

  const { push } = useToast();
  const fileInputRef = useRef<HTMLInputElement>(null);
  const listRef = useRef<HTMLDivElement>(null);
  const mediaRecorderRef = useRef<MediaRecorder | null>(null);
  const audioChunksRef = useRef<Blob[]>([]);
  const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const scrollToBottom = useCallback(() => {
    requestAnimationFrame(() => {
      listRef.current?.scrollTo({ top: listRef.current.scrollHeight, behavior: "smooth" });
    });
  }, []);

  const init = useCallback(async () => {
    try {
      const data = await apiFetch<{ conversationId: string; messages: ChatMessage[] }>(
        "/api/chat/guest/conversation",
        { method: "POST", body: JSON.stringify({}) }
      );
      setConversationId(data.conversationId);
      setMessages(data.messages);
      scrollToBottom();
    } catch {
      // silent — chat is a convenience feature
    }
  }, [scrollToBottom]);

  useEffect(() => {
    if (open && !conversationId) {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- start conversation when panel first opens
      init();
    }
  }, [open, conversationId, init]);

  useEffect(() => {
    if (!open || !conversationId) return;
    pollRef.current = setInterval(async () => {
      try {
        const data = await apiFetch<{ messages: ChatMessage[] }>(
          `/api/chat/guest/${conversationId}/messages`
        );
        setMessages(data.messages);
      } catch {
        /* ignore transient poll failures */
      }
    }, 4000);
    return () => {
      if (pollRef.current) clearInterval(pollRef.current);
    };
  }, [open, conversationId]);

  useEffect(() => {
    if (open) scrollToBottom();
  }, [messages, open, scrollToBottom]);

  async function sendMessage(kind: ChatMessage["kind"], content: string) {
    if (!conversationId) return;
    setSending(true);
    try {
      const data = await apiFetch<{ message: ChatMessage }>(
        `/api/chat/guest/${conversationId}/messages`,
        { method: "POST", body: JSON.stringify({ kind, content }) }
      );
      setMessages((prev) => [...prev, data.message]);
    } catch (err) {
      push(err instanceof Error ? err.message : "تعذر إرسال الرسالة", "error");
    } finally {
      setSending(false);
    }
  }

  async function handleSendText() {
    const value = text.trim();
    if (!value) return;
    setText("");
    await sendMessage("text", value);
  }

  async function uploadFile(file: File): Promise<string | null> {
    setUploading(true);
    try {
      const form = new FormData();
      form.append("file", file);
      const res = await fetch("/api/upload", { method: "POST", body: form });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error ?? "فشل رفع الملف");
      return data.url as string;
    } catch (err) {
      push(err instanceof Error ? err.message : "فشل رفع الملف", "error");
      return null;
    } finally {
      setUploading(false);
    }
  }

  async function handleFilePicked(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    const url = await uploadFile(file);
    if (url) await sendMessage("image", url);
  }

  async function toggleRecording() {
    if (recording) {
      mediaRecorderRef.current?.stop();
      setRecording(false);
      return;
    }
    try {
      const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
      const recorder = new MediaRecorder(stream);
      audioChunksRef.current = [];
      recorder.ondataavailable = (e) => audioChunksRef.current.push(e.data);
      recorder.onstop = async () => {
        stream.getTracks().forEach((t) => t.stop());
        const blob = new Blob(audioChunksRef.current, { type: "audio/webm" });
        const file = new File([blob], "voice.webm", { type: "audio/webm" });
        const url = await uploadFile(file);
        if (url) await sendMessage("audio", url);
      };
      mediaRecorderRef.current = recorder;
      recorder.start();
      setRecording(true);
    } catch {
      push("تعذر الوصول إلى الميكروفون", "error");
    }
  }

  return (
    <>
      <motion.button
        onClick={() => setOpen((v) => !v)}
        whileTap={{ scale: 0.92 }}
        className="fixed bottom-6 left-6 z-50 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/30"
        aria-label="الدردشة مع الدعم"
      >
        <AnimatePresence mode="wait">
          {open ? (
            <motion.span key="x" initial={{ rotate: -90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: 90, opacity: 0 }}>
              <X className="size-6" />
            </motion.span>
          ) : (
            <motion.span key="c" initial={{ rotate: 90, opacity: 0 }} animate={{ rotate: 0, opacity: 1 }} exit={{ rotate: -90, opacity: 0 }}>
              <MessageCircle className="size-6" />
            </motion.span>
          )}
        </AnimatePresence>
      </motion.button>

      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: 24, scale: 0.96 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 24, scale: 0.96 }}
            transition={{ duration: 0.2 }}
            className="fixed bottom-24 left-6 z-50 flex h-[28rem] w-[22rem] max-w-[calc(100vw-3rem)] flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-2xl"
          >
            <div className="flex items-center gap-2 border-b border-border bg-primary px-4 py-3 text-primary-foreground">
              <HeadphonesIcon className="size-5" />
              <div>
                <p className="text-sm font-bold">الدعم الفني</p>
                <p className="text-[11px] opacity-80">عادةً نرد خلال دقائق</p>
              </div>
            </div>

            <div ref={listRef} className="flex-1 space-y-3 overflow-y-auto px-3 py-4">
              {messages.length === 0 && (
                <p className="mt-8 text-center text-sm text-muted">
                  اكتب رسالتك وسنقوم بالرد عليك من لوحة الإدارة 👋
                </p>
              )}
              {messages.map((m) => (
                <div key={m.id} className={cn("flex", m.sender === "guest" ? "justify-end" : "justify-start")}>
                  <div
                    className={cn(
                      "max-w-[80%] rounded-2xl px-3.5 py-2 text-sm",
                      m.sender === "guest"
                        ? "rounded-bl-sm bg-primary text-primary-foreground"
                        : "rounded-br-sm bg-surface-muted text-foreground"
                    )}
                  >
                    {m.kind === "text" && <p className="whitespace-pre-wrap break-words">{m.content}</p>}
                    {m.kind === "image" && (
                      // eslint-disable-next-line @next/next/no-img-element
                      <img src={m.content} alt="صورة مرفقة" className="max-h-48 rounded-xl object-cover" />
                    )}
                    {m.kind === "audio" && <audio controls src={m.content} className="max-w-[220px]" />}
                    <p className="mt-1 text-[10px] opacity-60">{formatDateTime(m.createdAt)}</p>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex items-center gap-1.5 border-t border-border p-2.5">
              <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                className="hidden"
                onChange={handleFilePicked}
              />
              <button
                onClick={() => fileInputRef.current?.click()}
                disabled={uploading}
                className="flex size-9 shrink-0 items-center justify-center rounded-full text-muted transition-colors hover:bg-surface-muted disabled:opacity-50"
                title="إرفاق صورة"
              >
                <Paperclip className="size-4.5" />
              </button>
              <button
                onClick={toggleRecording}
                disabled={uploading}
                className={cn(
                  "flex size-9 shrink-0 items-center justify-center rounded-full transition-colors disabled:opacity-50",
                  recording ? "bg-danger text-white" : "text-muted hover:bg-surface-muted"
                )}
                title="تسجيل صوتي"
              >
                {recording ? <Square className="size-4" /> : <Mic className="size-4.5" />}
              </button>
              <input
                value={text}
                onChange={(e) => setText(e.target.value)}
                onKeyDown={(e) => e.key === "Enter" && handleSendText()}
                placeholder="اكتب رسالتك…"
                className="h-9 flex-1 rounded-full border border-border bg-background px-3.5 text-sm outline-none focus:border-primary"
              />
              <button
                onClick={handleSendText}
                disabled={sending || uploading || !text.trim()}
                className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground transition-transform active:scale-90 disabled:opacity-50"
              >
                {sending || uploading ? <Loader2 className="size-4 animate-spin" /> : <Send className="size-4" />}
              </button>
            </div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}
