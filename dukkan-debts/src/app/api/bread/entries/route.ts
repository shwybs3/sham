import { NextRequest, NextResponse } from "next/server";
import { z } from "zod";
import { prisma } from "@/lib/prisma";
import { getBreadSession } from "@/lib/auth";
import { jsonError } from "@/lib/api-helpers";

export async function GET() {
  const session = await getBreadSession();
  if (!session) return jsonError("يرجى تسجيل الدخول عبر جوجل أولاً", 401);

  const entries = await prisma.breadEntry.findMany({
    where: { customerId: session.customerId },
    orderBy: { entryDate: "desc" },
    take: 60,
  });

  return NextResponse.json({ entries });
}

const schema = z.object({
  quantity: z.number().int().positive().max(1000),
  note: z.string().trim().max(200).optional().or(z.literal("")),
});

export async function POST(request: NextRequest) {
  const session = await getBreadSession();
  if (!session) return jsonError("يرجى تسجيل الدخول عبر جوجل أولاً", 401);

  const body = await request.json().catch(() => null);
  const parsed = schema.safeParse(body);
  if (!parsed.success) return jsonError(parsed.error.issues[0]?.message ?? "بيانات غير صالحة");

  const entry = await prisma.breadEntry.create({
    data: {
      customerId: session.customerId,
      entryDate: new Date(),
      quantity: parsed.data.quantity,
      note: parsed.data.note || null,
    },
  });

  return NextResponse.json({ entry }, { status: 201 });
}
