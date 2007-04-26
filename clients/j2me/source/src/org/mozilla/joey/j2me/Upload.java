package org.mozilla.joey.j2me;

public class Upload
{
	private static final int STATUS_SHARED = 0;
	private static final int STATUS_LOCAL = 1;
	private static final int STATUS_EDITED = 2;

	private int status;
	private String id;
	private String name;
	private String description;
	private String mimetype;
	private String preview;
	private String data;
	private String modified;
	private String referrer;
	
	public Upload(String mimetype, String data, String modified)
	{
		this(STATUS_LOCAL, null, null, null, mimetype, null, data, modified, null);
	}

	public Upload(String id, String mimetype, String preview, String modified, String referrer)
	{
		this(STATUS_SHARED, id, null, null, mimetype, preview, null, modified, referrer);
	}

	public Upload(int status, String id, String name, String description, String mimetype, String preview, String data, String modified, String referrer)
	{
		this.status = status;
		this.id = id;
		this.name = name;
		this.description = description;
		this.mimetype = mimetype;
		this.preview = preview;
		this.data = data;
		this.modified = modified;
	}

	public boolean isShared()
	{
		return this.status == STATUS_SHARED;
	}
	
	public boolean isLocal()
	{
		return this.status == STATUS_LOCAL;
	}
	
	public boolean isEdited()
	{
		return this.status == STATUS_EDITED;
	}

	public String getId()
	{
		return this.id;
	}

	public void setId(String id)
	{
		this.id = id;
	}

	public String getData()
	{
		return this.data;
	}

	public void setData(String data)
	{
		this.data = data;
	}

	public String getDescription()
	{
		return this.description;
	}

	public void setDescription(String description)
	{
		this.description = description;
	}

	public String getName()
	{
		return this.name;
	}

	public void setName(String name)
	{
		this.name = name;
	}

	public String getMimetype()
	{
		return this.mimetype;
	}

	public void setMimetype(String mimetype)
	{
		this.mimetype = mimetype;
	}

	public String getPreview()
	{
		return this.preview;
	}

	public void setPreview(String preview)
	{
		this.preview = preview;
	}

	public String getModified()
	{
		return this.modified;
	}

	public void setModified(String modified)
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
