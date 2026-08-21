import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

export async function GET(_request: NextRequest, ctx: RouteContext<"/api/admin/chat/conversations/[id]/messages">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const messages = await prisma.chatMessage.findMany({
    where: { conversationId: id },
    orderBy: { createdAt: "asc" },
  });

  await prisma.chatMessage.updateMany({
    where: { conversationId: id, sender: "guest", readByAdmin: false },
    data: { readByAdmin: true },
  });

  return NextResponse.json({ messages });
}

const schema = z.object({
  kind: z.enum(["text", "image", "audio"]),
  content: z.string().trim().min(1).max(4000),
});

export async function POST(request: NextRequest, ctx: RouteContext<"/api/admin/chat/conversations/[id]/messages">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const conversation = await prisma.chatConversation.findUnique({ where: { id } });
  if (!conversation) return jsonError("المحادثة غير موجودة", 404);

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const message = await prisma.chatMessage.create({
    data: {
      conversationId: id,
      sender: "admin",
      kind: parsed.data.kind,
      content: parsed.data.content,
    },
  });

  await prisma.chatConversation.update({ where: { id }, data: { updatedAt: new Date() } });

  return NextResponse.json({ message }, { status: 201 });
}
