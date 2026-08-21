import { NextRequest, NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getBreadSession } from "@/lib/auth";
import { jsonError } from "@/lib/api-helpers";

export async function DELETE(_request: NextRequest, ctx: RouteContext<"/api/bread/entries/[id]">) {
  const session = await getBreadSession();
  if (!session) return jsonError("يرجى تسجيل الدخول عبر جوجل أولاً", 401);

  const { id } = await ctx.params;
  const entry = await prisma.breadEntry.findUnique({ where: { id } });
  if (!entry || entry.customerId !== session.customerId) {
    return jsonError("التسجيل غير موجود", 404);
  }

  await prisma.breadEntry.delete({ where: { id } });
  return NextResponse.json({ ok: true });
}
