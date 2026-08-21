"use client";

import { useState } from "react";
import { Modal } from "@/components/ui/Modal";
import { Input, Label, Textarea } from "@/components/ui/Input";
import { Button } from "@/components/ui/Button";
import { apiFetch } from "@/lib/api-client";
import { useToast } from "@/components/ui/Toast";

export function AddCustomerDialog({
  open,
  onClose,
  onCreated,
}: {
  open: boolean;
  onClose: () => void;
  onCreated: () => void;
}) {
  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [notes, setNotes] = useState("");
  const [loading, setLoading] = useState(false);
  const { push } = useToast();

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    try {
      await apiFetch("/api/customers", {
        method: "POST",
        body: JSON.stringify({ name, phone, notes }),
      });
      push("تمت إضافة الزبون", "success");
      setName("");
      setPhone("");
      setNotes("");
      onCreated();
    } catch (err) {
      push(err instanceof Error ? err.message : "تعذر الحفظ", "error");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Modal open={open} onClose={onClose} title="إضافة زبون جديد">
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <Label htmlFor="c-name">الاسم</Label>
          <Input id="c-name" value={name} onChange={(e) => setName(e.target.value)} required autoFocus />
        </div>
        <div>
          <Label htmlFor="c-phone">رقم الهاتف (اختياري)</Label>
          <Input id="c-phone" value={phone} onChange={(e) => setPhone(e.target.value)} dir="ltr" />
        </div>
        <div>
          <Label htmlFor="c-notes">ملاحظات (اختياري)</Label>
          <Textarea id="c-notes" value={notes} onChange={(e) => setNotes(e.target.value)} rows={2} />
        </div>
        <Button type="submit" className="w-full" loading={loading}>
          حفظ
        </Button>
      </form>
    </Modal>
  );
}
