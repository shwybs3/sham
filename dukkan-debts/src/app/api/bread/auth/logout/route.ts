import { NextResponse } from "next/server";
import { destroyBreadSession } from "@/lib/auth";

export async function POST() {
  await destroyBreadSession();
  return NextResponse.json({ ok: true });
}
