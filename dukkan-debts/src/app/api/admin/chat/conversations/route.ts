import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET() {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const conversations = await prisma.chatConversation.findMany({
    orderBy: { updatedAt: "desc" },
    include: {
      messages: { orderBy: { createdAt: "desc" }, take: 1 },
      _count: { select: { messages: { where: { sender: "guest", readByAdmin: false } } } },
    },
  });

  const result = conversations.map((c) => ({
    id: c.id,
    guestName: c.guestName,
    status: c.status,
    updatedAt: c.updatedAt,
    lastMessage: c.messages[0] ?? null,
    unreadCount: c._count.messages,
  }));

  return NextResponse.json({ conversations: result });
}
