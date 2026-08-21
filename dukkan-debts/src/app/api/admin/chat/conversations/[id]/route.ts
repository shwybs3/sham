import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { jsonError, requireAdmin, isErrorResponse } from "@/lib/api-helpers";

const schema = z.object({ status: z.enum(["open", "closed"]) });

export async function PATCH(request: NextRequest, ctx: RouteContext<"/api/admin/chat/conversations/[id]">) {
  const admin = await requireAdmin();
  if (isErrorResponse(admin)) return admin;

  const { id } = await ctx.params;
  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError("بيانات غير صالحة");

  const conversation = await prisma.chatConversation.update({
    where: { id },
    data: { status: parsed.data.status },
  });

  return NextResponse.json({ conversation });
}
