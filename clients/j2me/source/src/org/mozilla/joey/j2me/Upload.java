package org.mozilla.joey.j2me;

public class Upload
{
	private String id;
	private String name;
	private String description;
	private String mimetype;
	private String preview;
	private String data;
	private String modified;
	private String referrer;
	
	public Upload(String id, String mimetype, String preview, String modified, String referrer)
	{
		this(id, null, null, mimetype, preview, null, modified, referrer);
	}

	public Upload(String id, String name, String description, String mimetype, String preview, String data, String modified, String referrer)
	{
		this.id = id;
		this.name = name;
		this.description = description;
		this.mimetype = mimetype;
		this.preview = preview;
		this.data = data;
		this.modified = modified;
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
