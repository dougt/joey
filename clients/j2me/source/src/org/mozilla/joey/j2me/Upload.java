/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Mozilla Joey.
 *
 * The Initial Developer of the Original Code is
 * Michael Koch.
 * Portions created by the Initial Developer are Copyright (C) 2007
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */

package org.mozilla.joey.j2me;

public class Upload
{
	private long id;
	private String title;
	private String mimetype;
	private byte[] preview;
	private byte[] data;
	private long modified;
	private String referrer;
    private boolean deleted;

	public Upload(long id, boolean deleted)
	{
		this(id, "", "", null, null, 0, null);
        this.deleted = deleted;
	}
	
	public Upload(long id, String mimetype, String title, byte[] preview, byte[] data, long modified, String referrer)
	{
		this.id = id;
		this.title = title;
		this.mimetype = mimetype;
		this.preview = preview;
		this.data = data;
		this.modified = modified;
        this.deleted = false;
    }

	public boolean isDeleted()
	{
		return this.deleted;
	}
	
	public long getId()
	{
		return this.id;
	}

	public void setId(long id)
	{
		this.id = id;
	}

	public byte[] getData()
	{
		return this.data;
	}

	public void setData(byte[] data)
	{
		this.data = data;
	}

	public String getTitle()
	{
		return this.title;
	}

	public void setTitle(String title)
	{
		this.title = title;
	}

	public String getMimetype()
	{
		return this.mimetype;
	}

	public void setMimetype(String mimetype)
	{
		this.mimetype = mimetype;
	}

	public byte[] getPreview()
	{
		return this.preview;
	}

	public void setPreview(byte[] preview)
	{
		this.preview = preview;
	}

	public long getModified()
	{
		return this.modified;
	}

	public void setModified(long modified)
	{
		this.modified = modified;
	}

	public String getReferrer()
	{
		return this.referrer;
	}

	public void setReferrer(String referrer)
	{
		this.referrer = referrer;
	}
}
