import { AdminChatInbox } from "@/components/admin/AdminChatInbox";

export default function AdminChatPage() {
  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-extrabold">الدردشة مع الزبائن</h1>
        <p className="text-sm text-muted">رد على استفسارات الزوار مباشرة من هنا</p>
      </div>
      <AdminChatInbox />
    </div>
  );
}
